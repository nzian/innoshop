@extends('panel::layouts.app')
@section('body-class', '')

@section('title', __('panel/menu.customer_groups'))
@section('page-title-right')
<a href="{{ panel_route('customer_groups.create') }}" class="btn btn-primary">
  <i class="bi bi-plus-square"></i> {{ __('panel/common.create') }}</a>
@endsection

@section('content')
<div class="card" id="app">
  <div class="card-body">

    <x-panel-data-criteria :criteria="$criteria ?? []" :action="panel_route('customer_groups.index')" />

    @if ($groups->count())
    <div class="table-responsive">
      <table class="table align-middle rounded border">
        <thead>
          <tr>
            <td class="text-white">{{ __('panel/common.id') }}</td>
            <td class="text-white">{{ __('panel/common.name') }}</td>
            <td class="text-white">{{ __('panel/customer.level') }}</td>
            <td class="text-white">{{ __('panel/customer.mini_cost') }}</td>
            <td class="text-white">{{ __('panel/customer.discount_rate') }}</td>
            <td class="text-white">{{ __('panel/common.actions') }}</td>
          </tr>
        </thead>
        <tbody>
          @foreach($groups as $item)
          <tr>
            <td>{{ $item->id }}</td>
            <td>{{ $item->fallbackName() }}</td>
            <td>{{ $item->level }}</td>
            <td>{{ currency_format($item->mini_cost, system_setting('currency')) }}</td>
            <td>{{ $item->discount_rate }}</td>
            <td>
  <div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
            id="dropdownMenuButton{{ $item->id }}" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="bi bi-three-dots-vertical"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton{{ $item->id }}">
      <!-- Edit -->
      <li>
        <a class="dropdown-item" href="{{ panel_route('customer_groups.edit', [$item->id]) }}">
          <i class="bi bi-pencil-square"></i> {{ __('panel/common.edit') }}
        </a>
      </li>
      <!-- Delete -->
      <li>
        <form ref="deleteForm{{ $item->id }}" action="{{ panel_route('customer_groups.destroy', [$item->id]) }}" method="POST">
          @csrf
          @method('DELETE')
          <a class="dropdown-item text-danger" href="javascript:void(0)" @click="open({{ $item->id }})">
            <i class="bi bi-trash"></i> {{ __('panel/common.delete') }}
          </a>
        </form>
      </li>
    </ul>
  </div>
</td>

          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{ $groups->withQueryString()->links('panel::vendor/pagination/bootstrap-4') }}
    @else
    <x-common-no-data />
    @endif
  </div>
</div>
@endsection

@push('footer')
<script>
  const { createApp, ref } = Vue;
    const { ElMessageBox, ElMessage } = ElementPlus;

    const app = createApp({
    setup() {
    const deleteForm = ref(null);

     const open = (itemId) => {
    ElMessageBox.confirm(
       '{{ __("common/base.hint_delete") }}',
       '{{ __("common/base.cancel") }}',
       {
       confirmButtonText: '{{ __("common/base.confirm")}}',
       cancelButtonText: '{{ __("common/base.cancel")}}',
       type: 'warning',
       }
     )
     .then(() => {
    const deletUrl =urls.base_url +'/customer_groups/'+ itemId;
    deleteForm.value.action = deletUrl;
    deleteForm.value.submit();
    })
     .catch(() => {
     });
     };

     return { open, deleteForm };
     }
    });

     app.use(ElementPlus);
     app.mount('#app');
</script>
@endpush
