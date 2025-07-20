@extends('panel::layouts.app')
@section('body-class', '')

@section('title', __('panel/menu.articles'))
@section('page-title-right')
  <a href="{{ panel_route('articles.create') }}" class="btn btn-primary"><i
      class="bi bi-plus-square"></i> {{ __('panel/common.create') }}</a>
@endsection

@section('content')
  <div class="card h-min-600" id="app">
    <div class="card-body">

      <x-panel-data-criteria :criteria="$criteria ?? []" :action="panel_route('articles.index')"/>

      <div class="table-responsive">
        <table class="table align-middle rounded border">
          <thead>
          <tr>
            <td class="text-white">{{ __('panel/common.id')}}</td>
            <td class="text-white">{{ __('panel/article.image') }}</td>
            <td class="text-white">{{ __('panel/article.title') }}</td>
            <td class="text-white">{{ __('panel/article.catalog') }}</td>
            <td class="text-white">{{ __('panel/article.tag') }}</td>
            <td class="text-white">{{ __('panel/common.slug') }}</td>
            <td class="text-white">{{ __('panel/common.position') }}</td>
            <td class="text-white">{{ __('panel/common.active') }}</td>
            <td class="text-white">{{ __('panel/common.actions') }}</td>
          </tr>
          </thead>
          @if ($articles->count())
            <tbody>
            @foreach($articles as $item)
              <tr>
                <td>{{ $item->id }}</td>
                <td><img src="{{ image_resize($item->fallbackName('image'), 30, 30) }}" class="wh-30"></td>
                <td>
                  <a href="{{ $item->url }}" target="_blank" class="text-decoration-none text-bold-primary-color">
                    {{ sub_string($item->fallbackName('title'), 32) }}
                  </a>
                </td>
                <td>{{ $item->catalog->translation->title ?? '-' }}</td>
                <td>{{ $item->tagNames ?: '-' }}</td>
                <td>{{ sub_string($item->slug ?: '-') }}</td>
                <td>{{ $item->position }}</td>
                <td>@include('panel::shared.list_switch', ['value' => $item->active, 'url' => panel_route('articles.active', $item->id)])</td>
                <td>
  <div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
            id="dropdownMenuButton{{ $item->id }}" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="bi bi-three-dots-vertical"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton{{ $item->id }}">
      <!-- Edit -->
      <li>
        <a class="dropdown-item" href="{{ panel_route('articles.edit', [$item->id]) }}">
          <i class="bi bi-pencil-square"></i> {{ __('panel/common.edit') }}
        </a>
      </li>
      <!-- Delete -->
      <li>
        <form ref="deleteForm{{ $item->id }}" action="{{ panel_route('articles.destroy', [$item->id]) }}" method="POST">
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
          @else
            <tbody>
            <tr>
              <td colspan="5">
                <x-common-no-data/>
              </td>
            </tr>
            </tbody>
          @endif
        </table>
      </div>
      {{ $articles->withQueryString()->links('panel::vendor/pagination/bootstrap-4') }}
    </div>
  </div>
@endsection

@push('footer')
  <script>
    const {createApp, ref} = Vue;
    const {ElMessageBox, ElMessage} = ElementPlus;

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
          ).then(() => {
            deleteForm.value.action = urls.base_url + '/articles/' + index;
            deleteForm.value.submit();
          }).catch(() => {
          });
        };

        return {open, deleteForm};
      }
    });

    app.use(ElementPlus);
    app.mount('#app');
  </script>
@endpush
