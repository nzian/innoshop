@extends('panel::layouts.app')
@section('body-class', 'page-page')

@section('title', __('panel/menu.pages'))
@section('page-title-right')
<a href="{{ panel_route('pages.create') }}" class="btn btn-primary"><i class="bi bi-plus-square"></i> {{
  __('panel/common.create') }}</a>
@endsection

@section('content')
<div class="card h-min-600" id="app">
  <div class="card-body">

    <x-panel-data-criteria :criteria="$criteria ?? []" :action="panel_route('pages.index')" />

    @if ($pages->count())
    <div class="table-responsive">
      <table class="table align-middle rounded border">
        <thead>
          <tr>
            <td class="text-white">{{ __('panel/common.id')}}</td>
            <td class="text-white">{{ __('panel/article.title') }}</td>
            <td class="text-white">{{ __('panel/common.slug') }}</td>
            <td class="text-white">{{ __('panel/common.viewed') }}</td>
            <td class="text-white">{{ __('panel/common.active') }}</td>
            <td class="text-white">{{ __('panel/common.actions') }}</td>
          </tr>
        </thead>
        <tbody>
          @foreach($pages as $item)
          <tr>
            <td>{{ $item->id }}</td>
            <td><a href="{{ $item->url }}" target="_blank" class="text-decoration-none text-bold-primary-color">{{ $item->fallbackName('title') }}</a></td>
            <td>{{ $item->slug }}</td>
            <td>{{ $item->viewed }}</td>
            <td>@include('panel::shared.list_switch', ['value' => $item->active, 'url' => panel_route('pages.active',
              $item->id)])</td>
            <td>
  <div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
            id="dropdownMenuButton{{ $item->id }}" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="bi bi-three-dots-vertical"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton{{ $item->id }}">
      <!-- Edit -->
      <li>
        <a class="dropdown-item" href="{{ panel_route('pages.edit', [$item->id]) }}">
          <i class="bi bi-pencil-square"></i> {{ __('panel/common.edit') }}
        </a>
      </li>

      <!-- Delete -->
      <li>
        <form ref="deleteForm{{ $item->id }}" action="{{ panel_route('pages.destroy', [$item->id]) }}" method="POST">
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
    {{ $pages->withQueryString()->links('panel::vendor/pagination/bootstrap-4') }}
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
      const deleteUrl =urls.base_url +'/pages/'+index;
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
