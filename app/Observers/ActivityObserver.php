<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityObserver
{
    public static function safe(array $values): array
    {
        $safe = [];
        foreach ($values as $key => $value) {
            if (preg_match('/password|token|secret|otp|verification|remember|api_key|message/i', (string) $key)) {
                continue;
            }
            $safe[$key] = is_array($value) ? self::safe($value) : $value;
        }

        return $safe;
    }

    public function created(Model $model): void
    {
        $this->record($model, 'created', [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);
        if ($changes) {
            $this->record($model, 'updated', array_intersect_key($model->getRawOriginal(), $changes), $changes);
        }
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', $model->getAttributes(), []);
    }

    private function record(Model $model, string $event, array $before, array $after): void
    {
        ActivityLog::create(['user_id' => auth()->id(), 'action' => $event,
            'subject_type' => $model->getTable(), 'subject_id' => (string) $model->getKey(),
            'before' => self::safe($before), 'after' => self::safe($after)]);
    }
}
