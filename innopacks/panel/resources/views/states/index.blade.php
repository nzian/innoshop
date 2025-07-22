@extends('panel::layouts.app')
@section('body-class', '')

@section('title', __('panel/menu.states'))

@section('content')
  <div class="card h-min-600" id="app">
    <div class="card-body">

      <x-panel-data-criteria :criteria="$criteria ?? []" :action="panel_route('states.index')"/>

      @if ($states)
        <div class="table-responsive">
          <table class="table align-middle rounded border">
            <thead>
              <tr>
                <th>{{ __('panel/common.id') }}</th>
                <th>{{ __('panel/state.name') }}</th>
                <th>{{ __('panel/state.code') }}</th>
                <th>{{ __('panel/state.country_code') }}</th>
                <th>{{ __('panel/state.position') }}</th>
                <th>{{ __('panel/state.active') }}</th>
                <th>{{ __('panel/common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($states as $item)
                <tr>
                  <td>{{ $item['id'] }}</td>
                  <td>
                    {{ $item['name'] }}
                    @if($item['code'] === system_setting('state_code'))
                      <span class="badge bg-success">{{ __('panel/common.default') }}</span>
                    @endif
                  </td>
                  <td>{{ $item['code'] }}</td>
                  <td>{{ $item['country_code'] }}</td>
                  <td>{{ $item['position'] }}</td>
                  <td>
                    @include('panel::shared.list_switch', [
                      'value' => $item->active,
                      'url' => panel_route('states.active', $item->id)
                    ])
                  </td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                              type="button"
                              id="dropdownMenuButton{{ $item->id }}"
                              data-bs-toggle="dropdown"
                              data-bs-display="static"
                              aria-expanded="false">
                        <i class="bi bi-three-dots-vertical"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton{{ $item->id }}">
                        <!-- Edit Option -->
                        <li>
                          <a class="dropdown-item" href="javascript:void(0)" @click="edit({{ $item->id }})">
                            <i class="bi bi-pencil-square"></i> {{ __('panel/common.edit') }}
                          </a>
                        </li>
                        <!-- Delete Option -->
                        <li>
                          <a class="dropdown-item text-danger" href="javascript:void(0)" @click="open({{ $item->id }})">
                            <i class="bi bi-trash"></i> {{ __('panel/common.delete') }}
                          </a>
                        </li>
                      </ul>
                    </div>
                    <!-- Hidden Delete Form -->
                    <form ref="deleteForm" action="{{ panel_route('states.destroy', [$item->id]) }}" method="POST" style="display: none;">
                      @csrf
                      @method('DELETE')
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        {{ $states->withQueryString()->links('panel::vendor/pagination/bootstrap-4') }}
      @else
        <x-common-no-data/>
      @endif
    </div>

    <!-- Drawer Form -->
    <el-drawer v-model="drawer" size="500" @close="close">
      <template #header>
        <div class="text-dark fs-4">{{ __('panel/menu.states') }}</div>
      </template>

      <el-form ref="formRef" label-position="top" :model="form" :rules="rules" label-width="auto" status-icon>
        <el-form-item label="{{ __('panel/common.name') }}" prop="name">
          <el-input v-model="form.name" placeholder="{{ __('panel/common.name') }}"></el-input>
        </el-form-item>

        <el-form-item label="{{ __('panel/state.code') }}" prop="code">
          <el-input v-model="form.code" placeholder="{{ __('panel/state.code') }}"></el-input>
        </el-form-item>

        <el-form-item label="{{ __('panel/state.country_code') }}" prop="country_id">
          <select v-model="form.country_id" class="form-control"
                  @change="form.country_code = countries.find(item => item.id == form.country_id).code">
            <option v-for="item in countries" :value="item.id">@{{ item.name }}</option>
          </select>
        </el-form-item>

        <el-form-item label="{{ __('panel/state.position') }}" prop="position">
          <el-input v-model="form.position" placeholder="{{ __('panel/state.position') }}"></el-input>
        </el-form-item>

        <el-form-item label="{{ __('panel/state.active') }}" prop="active">
          <el-switch v-model="form.active" :active-value="1" :inactive-value="0"></el-switch>
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
const api = @json(panel_route('states.index'));
const { createApp, ref, reactive, onMounted, getCurrentInstance } = Vue;
const { ElMessageBox } = ElementPlus;

const listApp = createApp({
  setup() {
    const countries = ref([]);
    const drawer = ref(false);
    const { proxy } = getCurrentInstance();

    const form = reactive({
      id: 0,
      name: '',
      code: '',
      country_code: '',
      country_id: '',
      position: '0',
      active: 1
    });

    const rules = {};

    onMounted(() => {
      getCountries();
    });

    const edit = (id) => {
      drawer.value = true;
      axios.get(`${api}/${id}`).then((res) => {
        Object.keys(res).forEach(key => form.hasOwnProperty(key) && (form[key] = res[key]));
      });
    };

    const submit = () => {
      const url = form.id ? `${api}/${form.id}` : api;
      const method = form.id ? 'put' : 'post';
      axios[method](url, form).then((res) => {
        drawer.value = false;
        inno.msg(res.message);
        window.location.reload();
      });
    };

    const close = () => {
      proxy.$refs.formRef.resetFields();
    };

    const create = () => {
      drawer.value = true;
    };

    const getCountries = () => {
      axios.get('{{ front_route('countries.index') }}').then((res) => {
        countries.value = res.data;
      });
    };

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
      ).then(() => {
        const deleteUrl = urls.base_url + '/states/' + itemId;
        deleteForm.value.action = deleteUrl;
        deleteForm.value.submit();
      }).catch(() => {});
    };

    return {
      drawer,
      form,
      edit,
      rules,
      close,
      submit,
      create,
      countries,
      deleteForm,
      open
    };
  }
});

listApp.use(ElementPlus);
listApp.mount('#app');
</script>
@endpush
