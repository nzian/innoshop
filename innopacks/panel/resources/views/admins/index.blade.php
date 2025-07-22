@extends('panel::layouts.app')
@section('body-class', '')

@section('title', __('panel/menu.admins'))
@section('page-title-right')
<a href="{{ panel_route('admins.create') }}" class="btn btn-primary"><i class="bi bi-plus-square"></i> {{
  __('panel/common.create') }}</a>
@endsection

@section('content')
<div class="card h-min-600" id="app">
  <div class="card-body">

    <x-panel-data-criteria :criteria="$criteria ?? []" :action="panel_route('admins.index')" />

    @if ($admins->count())
    <div class="table-responsive">
      <table class="table align-middle rounded border">
        <thead>
          <tr>
            <td class="text-white">{{ __('panel/common.id') }}</td>
            <td class="text-white">{{ __('panel/admin.name') }}</td>
            <td class="text-white">{{ __('panel/admin.email') }}</td>
            <td class="text-white">{{ __('panel/admin.locale') }}</td>
            <td class="text-white">{{ __('panel/admin.roles') }}</td>
            <td class="text-white">{{ __('panel/admin.active') }}</td>
            <td class="text-white">{{ __('panel/common.actions') }}</td>
          </tr>
        </thead>
        <tbody>
          @foreach($admins as $item)
          <tr>
            <td>{{ $item->id }}</td>
            <td>{{ $item->name }}</td>
            <td>{{ $item->email }}</td>
            <td>{{ $item->locale }}</td>
            <td>{{ $item->getRoleLabel() }}</td>
            <td>@include('panel::shared.list_switch', ['value' => $item->active, 'url' => panel_route('admins.active',
              $item->id)])</td>
            <td>
              <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="bi bi-three-dots-vertical"></i> <!-- Bootstrap icon -->
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a class="dropdown-item" href="{{ panel_route('admins.edit', [$item->id]) }}">
                      {{ __('panel/common.edit') }}
                    </a>
                  </li>
                  <li>
                    <form action="{{ panel_route('admins.destroy', [$item->id]) }}" method="POST" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="dropdown-item text-danger">
                        {{ __('panel/common.delete') }}
                      </button>
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
    {{ $admins->withQueryString()->links('panel::vendor/pagination/bootstrap-4') }}
    @else
    <x-common-no-data />
    @endif
  </div>
</div>
@endsection

@push('footer')
<script>
  const {
    createApp,
    ref
  } = Vue;
  const {
    ElMessageBox,
    ElMessage
  } = ElementPlus;

  const app = createApp({
    setup() {
      const deleteForm = ref(null);

      const open = (index) => {
        ElMessageBox.confirm(
            '{{ __("common/base.hint_delete") }}',
            '{{ __("common/base.cancel") }}', {
              confirmButtonText: '{{ __("common/base.confirm")}}',
              cancelButtonText: '{{ __("common/base.cancel")}}',
              type: 'warning',
            }
          )
          .then(() => {
            const deleteUrl = urls.base_url + '/admins/' + index;
            deleteForm.value.action = deleteUrl;
            deleteForm.value.submit();
          })
          .catch(() => {});
      };

      return {
        open,
        deleteForm
      };
    }
  });

  app.use(ElementPlus);
  app.mount('#app');
</script>
@endpush