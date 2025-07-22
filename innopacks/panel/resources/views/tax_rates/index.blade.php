@extends('panel::layouts.app')
@section('body-class', '')

@section('title', __('panel/menu.tax_rates'))

@push('header')
@endpush

@section('page-title-right')
<button type="button" class="btn btn-primary btn-add" onclick="app.create()">
  <i class="bi bi-plus-square"></i> {{ __('panel/common.create') }}
</button>
@endsection

@section('content')
<div class="card h-min-600" id="app">
  <div class="card-body">

    <x-panel-data-criteria :criteria="$criteria ?? []" :action="panel_route('tax_rates.index')" />

    @if ($tax_rates->count())
    <div class="table-responsive">
      <table class="table align-middle rounded border">
        <thead>
          <tr>
            <td class="text-white">{{ __('panel/common.id') }}</td>
            <td class="text-white">{{ __('panel/menu.regions') }}</td>
            <td class="text-white">{{ __('panel/tax_classes.taxes') }}</td>
            <td class="text-white">{{ __('panel/tax_classes.type') }}</td>
            <td class="text-white">{{ __('panel/tax_classes.tax_rate') }}</td>
            <td class="text-white">{{ __('panel/common.created_at') }}</td>
            <td class="text-white">{{ __('panel/common.actions') }}</td>
          </tr>
        </thead>
        <tbody>
          @foreach($tax_rates as $item)
          <tr>
            <td>{{ $item->id }}</td>
            <td>{{ $item->region->name }}</td>
            <td>{{ $item->name }}</td>
            <td>{{ $item->type }}</td>
            <td>{{ $item->rate }}</td>
            <td>{{ $item->created_at }}</td>
            <td>
              <!-- Bootstrap 3-dots dropdown -->
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        type="button"
                        id="dropdownMenuButton{{ $item->id }}"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                  <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton{{ $item->id }}">
                  <li>
                    <a class="dropdown-item" href="javascript:void(0)" @click="edit({{ $item->id }})">
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
              <!-- Hidden delete form -->
              <form ref="deleteForm" action="{{ panel_route('tax_rates.destroy', [$item->id]) }}" method="POST" style="display:none;">
                @csrf
                @method('DELETE')
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{ $tax_rates->withQueryString()->links('panel::vendor/pagination/bootstrap-4') }}
    @else
    <x-common-no-data />
    @endif
  </div>

  <el-drawer v-model="drawer" size="500" @close="close">
    <template #header>
      <div class="text-dark fs-4">{{ __('panel/menu.regions') }}</div>
    </template>
    <el-form ref="formRef" label-position="top" :model="form" :rules="rules" label-width="auto" status-icon>
      <el-form-item label="{{ __('panel/tax_classes.taxes') }}" prop="name">
        <el-input size="large" v-model="form.name"></el-input>
      </el-form-item>

      <el-form-item label="{{ __('panel/tax_classes.type') }}" prop="type">
        <select v-model="form.type" class="form-control">
          <option v-for="item in source.types" :value="item.value">@{{ item.label }}</option>
        </select>
      </el-form-item>

      <el-form-item label="{{ __('panel/tax_classes.tax_rate') }}" prop="rate">
        <el-input size="large" v-model="form.rate">
          <template #append v-if="form.type == 'percent'">%</template>
        </el-input>
      </el-form-item>

      <el-form-item label="{{ __('panel/menu.regions') }}" prop="region_id">
        <select v-model="form.region_id" class="form-control">
          <option v-for="item in source.regions" :value="item.id">@{{ item.name }}</option>
        </select>
      </el-form-item>
    </el-form>

    <template #footer>
      <div style="flex: auto">
        <el-button @click="drawer = false">{{ __('panel/common.close') }}</el-button>
        <el-button type="primary" @click="submit">{{ __('panel/common.btn_save') }}</el-button>
      </div>
    </template>
  </el-drawer>
</div>
@endsection

@push('footer')
<script>
  const { createApp, ref, reactive, getCurrentInstance } = Vue;
  const { ElMessageBox } = ElementPlus;
  const api = @json(panel_route('tax_rates.index'));

  const listApp = createApp({
    setup() {
      const drawer = ref(false)
      const { proxy } = getCurrentInstance();
      const source = reactive({
        regions: @json($regions ?? []),
        types: @json($types ?? []),
      })

      const form = reactive({
        id: 0,
        name: '',
        type: source.types[0]?.value ?? '',
        rate: '',
        region_id: source.regions[0]?.id ?? '',
      })

      const rules = {}

      const edit = (id) => {
        drawer.value = true
        axios.get(`${api}/${id}`).then((res) => {
          Object.keys(res).forEach(key => form.hasOwnProperty(key) && (form[key] = res[key]));
        })
      }

      const submit = () => {
        const url = form.id ? `${api}/${form.id}` : api
        const method = form.id ? 'put' : 'post'
        axios[method](url, form).then((res) => {
          drawer.value = false
          inno.msg(res.message)
          window.location.reload()
        })
      }

      const close = () => {
        proxy.$refs.formRef.resetFields()
      }

      const create = () => {
        drawer.value = true
      }

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
          const deleteUrl = `{{ panel_name() }}/tax_rates/${itemId}`;
          axios.delete(deleteUrl).then(() => {
            window.location.reload();
          }).catch(err => inno.msg(err.response.data.message));
        })
        .catch(() => {});
      };

      const exportFuns = {
        drawer,
        form,
        edit,
        rules,
        source,
        close,
        submit,
        create,
        deleteForm,
        open
      }

      window.app = exportFuns
      return exportFuns;
    }
  })

  listApp.use(ElementPlus);
  listApp.mount('#app');

  $(function () {
    $('.btn-add').click(function () {
      app.drawer.value = true
    })
  })
</script>
@endpush
