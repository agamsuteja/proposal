@if(session('success'))
    <div class="budget-note success" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
@endif
@if($errors->any())
    <div class="budget-note danger" role="alert"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
