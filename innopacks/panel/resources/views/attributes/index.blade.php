@extends('panel::layouts.app')
@section('body-class', 'page-page')

@section('title', __('panel/menu.attributes'))
@section('page-title-right')
<a href="{{ panel_route('attributes.create') }}" class="btn btn-primary"><i class="bi bi-plus-square"></i> {{
  __('panel/common.create') }}</a>
@endsection

@section('content')
<div class="card h-min-600" id="app">
  <div class="card-body">

    <x-panel-data-criteria :criteria="$criteria ?? []" :action="panel_route('attributes.index')" />

    @if ($attributes->count())
    <div class="table-responsive">
      <table class="table align-middle rounded border">
        <thead>
          <tr>
            <td class="text-white">{{ __('panel/common.id')}}</td>
            <td class="text-white">{{ __('panel/common.name')}}</td>
            <td class="text-white">{{ __('panel/menu.attribute_groups')}}</td>
            <td class="text-white">{{ __('panel/common.position')}}</td>
            <td class="text-white">{{ __('panel/common.created_at')}}</td>
            <td class="text-white">{{ __('panel/common.actions')}}</td>
          </tr>
        </thead>
        <tbody>
          @foreach($attributes as $item)
          <tr>
            <td>{{ $item->id }}</td>
            <td>{{ $item->fallbackName() }}</td>
            <td>{{ $item->group ? $item->group->fallbackName() : '' }}</td>
            <td>{{ $item->position }}</td>
            <td>{{ $item->created_at }}</td>
            <td>
  <div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton{{ $item->id }}" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="bi bi-three-dots-vertical"></i>
    </button>
    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $item->id }}">
      <li>
        <a class="dropdown-item" href="{{ panel_route('attributes.edit', [$item->id]) }}">
          <i class="bi bi-pencil-square"></i> {{ __('panel/common.edit') }}
        </a>
      </li>
      <li>
        <a class="dropdown-item text-danger" href="javascript:void(0)" @click="open({{ $item->id }})">
          <i class="bi bi-trash"></i> {{ __('panel/common.delete') }}
        </a>
      </li>
    </ul>
  </div>
  <form ref="deleteForm" action="{{ panel_route('attributes.destroy', [$item->id]) }}" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
  </form>
</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{ $attributes->withQueryString()->links('panel::vendor/pagination/bootstrap-4') }}
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

       const open = (index) => {
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
       const deleteUrl=urls.base_url+'/attributes/' +index;
       deleteForm.value.action=deleteUrl;
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
