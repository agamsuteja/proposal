<?php

use App\Http\Controllers\Backend\BudgetController;
use App\Http\Controllers\Backend\PencairanDanaController;
use App\Http\Controllers\Backend\PengembalianDanaController;
use App\Models\User;
use App\Services\DepartmentBudgetService;
use App\Services\FonnteService;
use App\Services\GroupFundingService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

chdir(dirname(__DIR__, 2));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
[$script,$database,$kind,$payload,$start] = $argv;
if (! str_starts_with($database, 'proposal_budget_test')) {
    exit(2);
}
config(['database.default' => 'mysql', 'database.connections.mysql.database' => $database, 'cache.default' => 'array', 'session.driver' => 'array']);
DB::purge('mysql');
$data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
$user = User::findOrFail($data['_actor']);
unset($data['_actor']);
Auth::setUser($user);
$app->instance(FonnteService::class, new class extends FonnteService
{
    public function send(?string $phone, string $message): bool
    {
        return false;
    }
});
$request = Request::create('/audit', 'POST', $data);
$request->setUserResolver(fn () => $user);
while (microtime(true) < (float) $start) {
    usleep(1000);
}
try {
    $response = match ($kind) {
        'payout' => (new PencairanDanaController)->store($request),
        'refund' => (new PengembalianDanaController)->store($request),
        'funding' => (new BudgetController)->saveFunding($request, app(GroupFundingService::class)),
        'allocation' => (new BudgetController)->save($request, app(DepartmentBudgetService::class)),
        default => throw new RuntimeException('Unsupported financial test'),
    };
    echo $response->getStatusCode();
} catch (ValidationException $e) {
    echo '422';
} catch (HttpExceptionInterface $e) {
    echo $e->getStatusCode();
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e).': '.$e->getMessage());
    exit(1);
}
