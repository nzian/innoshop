@extends('panel::layouts.app')
@section('body-class', '')

@section('title', __('panel/menu.tax_classes'))

@push('header')
<script src="{{ asset('vendor/vue/3.5/vue.global' . (!config('app.debug') ? '.prod' : '') . '.js') }}"></script>
<script src="{{ asset('vendor/element-plus/index.full.js') }}"></script>
<script src="{{ asset('vendor/element-plus/icons.min.js') }}"></script>
@endpush

@section('page-title-right')
<button type="button" class="btn btn-primary btn-add" onclick="app.create()">
  <i class="bi bi-plus-square"></i> {{ __('panel/common.create') }}
</button>
@endsection

@section('content')
<div class="card h-min-600" id="app">
  <div class="card-body ">

    <x-panel-data-criteria :criteria="$criteria ?? []" :action="panel_route('tax_classes.index')" />

    @if ($tax_classes->count())
    <div class="table-responsive h-min-600">
      <table class="table align-middle rounded border ">
        <thead>
          <tr>
            <td class="text-white">{{ __('panel/common.id')}}</td>
            <td class="text-white">{{ __('panel/common.name') }}</td>
            <td class="text-white">{{ __('panel/common.description') }}</td>
            <td class="text-white">{{ __('panel/common.created_at') }}</td>
            <td class="text-white">{{ __('panel/common.actions') }}</td>
          </tr>
        </thead>
        <tbody>
          @foreach($tax_classes as $item)
          <tr>
            <td>{{ $item->id }}</td>
            <td>{{ $item->name }}</td>
            <td>{{ $item->description }}</td>
            <td>{{ $item->created_at }}</td>
            <td>
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

              <!-- Hidden Delete Form -->
              <form ref="deleteForm" action="{{ panel_route('tax_classes.destroy', [$item->id]) }}" 
                    method="POST" style="display:none;">
                @csrf
                @method('DELETE')
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{ $tax_classes->withQueryString()->links('panel::vendor/pagination/bootstrap-4') }}
    @else
    <x-common-no-data />
    @endif
  </div>

  <el-drawer v-model="drawer" size="500" @close="close">
    <template #header>
      <div class="text-dark fs-4">{{ __('panel/menu.tax_classes') }}</div>
    </template>
    <el-form ref="formRef" label-position="top" :model="form" :rules="rules" label-width="auto" status-icon>
      <el-form-item label="{{ __('panel/common.name') }}" prop="name">
        <el-input size="large" v-model="form.name" placeholder="{{ __('panel/common.name') }}"></el-input>
      </el-form-item>

      <el-form-item label="{{ __('panel/common.description') }}" prop="description">
        <el-input size="large" v-model="form.description" placeholder="{{ __('panel/common.description') }}"></el-input>
      </el-form-item>

      <el-form-item label="{{ __('panel/tax_classes.rule') }}">
        <table class="table table-bordered regions-table">
          <thead>
            <tr>
              <th width="30%">{{ __('panel/tax_classes.tax_rate_id') }}</th>
              <th width="30%">{{ __('panel/tax_classes.based') }}</th>
              <th width="20%">{{ __('panel/tax_classes.priority') }}</th>
              <th class="text-end" width="90"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in form.tax_rules" :key="index">
              <td>
                <select class="form-select form-select-sm country-select" v-model="item.tax_rate_id" required>
                  <option v-for="item in source.tax_rates" :key="item.id" :value="item.id">@{{ item.name }}</option>
                </select>
              </td>
              <td>
                <select class="form-select form-select-sm" v-model="item.based" required>
                  <option v-for="item in source.address_types" :key="item.code" :value="item.code">@{{ item.label }}
                  </option>
                </select>
              </td>
              <td>
                <input type="text" class="form-control form-control-sm" v-model="item.priority"
                       placeholder="{{ __('panel/tax_classes.priority') }}">
              </td>
              <td class="text-end">
                <el-button type="danger" @click="form.tax_rules.splice(index, 1)">{{ __('panel/common.delete') }}</el-button>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" class="text-end">
                <el-button type="primary" @click="addItem">{{ __('panel/common.add') }}</el-button>
              </td>
            </tr>
          </tfoot>
        </table>
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
  const { ElMessageBox, ElMessage } = ElementPlus;
  const api = @json(panel_route('tax_classes.index'));

  const listApp = createApp({
    setup() {
      const drawer = ref(false)
      const { proxy } = getCurrentInstance();

      const form = reactive({
        id: 0,
        name: '',
        description: '',
        tax_rules: [],
      })

      const source = reactive({
        tax_rates : @json($tax_rates ?? []),
        address_types : @json($address_types ?? []),
      });

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
          inno.msg(res.message)
          drawer.value = false
          window.location.reload()
        })
      }

      const deleteForm = ref(null);
      const close = () => proxy.$refs.formRef.resetFields()
      const create = () => { drawer.value = true }

      const addItem = () => {
        form.tax_rules.push({
          tax_rate_id: source.tax_rates[0]?.id || 0,
          based: source.address_types[0]?.code || '',
          priority: 0,
        })
      }

      const open = (itemId) => {
        ElMessageBox.confirm(
          '{{ __("common/base.hint_delete") }}',
          '{{ __("common/base.cancel") }}',
          {
            confirmButtonText: '{{ __("common/base.confirm")}}',
            cancelButtonText: '{{ __("common/base.cancel")}}',
            type: 'warning',
          }
        ).then(() => {
          axios.delete(`{{ panel_name() }}/tax_classes/${itemId}`).then((res) => {
            window.location.reload();
          }).catch((err) => inno.msg(err.response.data.message));
        }).catch(() => {});
      };

      return { drawer, form, edit, rules, close, submit, create, source, addItem, open, deleteForm }
    }
  })

  listApp.use(ElementPlus);
  listApp.mount('#app');

  $(function () {
    $('.btn-add').click(() => app.drawer.value = true)
  })
</script>
@endpush
