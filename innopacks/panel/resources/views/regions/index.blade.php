@extends('panel::layouts.app')
@section('body-class', '')

@section('title', __('panel/menu.regions'))

@push('header')
  <script src="{{ asset('vendor/vue/3.5/vue.global' . (!config('app.debug') ? '.prod' : '') . '.js') }}"></script>
  <script src="{{ asset('vendor/element-plus/index.full.js') }}"></script>
  <script src="{{ asset('vendor/element-plus/icons.min.js') }}"></script>
@endpush

@section('page-title-right')
  <button type="button" class="btn btn-primary btn-add" onclick="regionList.create()">
    <i class="bi bi-plus-square"></i> {{ __('panel/common.create') }}
  </button>
@endsection

@section('content')
  <div class="card h-min-600" id="app">
    <div class="card-body">

      <x-panel-data-criteria :criteria="$criteria ?? []" :action="panel_route('regions.index')"/>

      @if ($regions)
        <div class="table-responsive">
          <table class="table align-middle rounded border">
            <thead>
              <tr>
                <td class="text-white">{{ __('panel/common.id') }}</td>
                <td class="text-white">{{ __('panel/region.name') }}</td>
                <td class="text-white">{{ __('panel/region.description') }}</td>
                <td class="text-white">{{ __('panel/common.active') }}</td>
                <td class="text-white">{{ __('panel/common.actions') }}</td>
              </tr>
            </thead>
            <tbody>
              @foreach($regions as $item)
                <tr>
                  <td>{{ $item['id'] }}</td>
                  <td>{{ $item['name'] }}</td>
                  <td>{{ $item['description'] }}</td>
                  <td>
                    @include('panel::shared.list_switch', [
                      'value' => $item->active, 
                      'url' => panel_route('regions.active', $item->id)
                    ])
                  </td>
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
                        <!-- Edit -->
                        <li>
                          <a class="dropdown-item" href="javascript:void(0)" @click="edit({{ $item->id }})">
                            <i class="bi bi-pencil-square"></i> {{ __('panel/common.edit') }}
                          </a>
                        </li>
                        <!-- Delete -->
                        <li>
                          <a class="dropdown-item text-danger" href="javascript:void(0)" @click="open({{ $item->id }})">
                            <i class="bi bi-trash"></i> {{ __('panel/common.delete') }}
                          </a>
                        </li>
                      </ul>
                    </div>

                    <!-- Hidden Delete Form -->
                    <form ref="deleteForm" action="{{ panel_route('regions.destroy', [$item->id]) }}" method="POST" style="display:none;">
                      @csrf
                      @method('DELETE')
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        {{ $regions->withQueryString()->links('panel::vendor/pagination/bootstrap-4') }}
      @else
        <x-common-no-data/>
      @endif
    </div>

    <!-- Drawer Form -->
    <el-drawer v-model="drawer" size="500" @close="close">
      <template #header>
        <div class="text-dark fs-4">{{ __('panel/menu.regions') }}</div>
      </template>
      <el-form ref="formRef" label-position="top" :model="form" :rules="rules" label-width="auto" status-icon>
        <el-form-item label="{{ __('panel/region.name') }}" prop="name">
          <el-input v-model="form.name" placeholder="{{ __('panel/region.name') }}"></el-input>
        </el-form-item>
        <el-form-item label="{{ __('panel/region.description') }}" prop="description">
          <el-input v-model="form.description" placeholder="{{ __('panel/region.description') }}"></el-input>
        </el-form-item>
        <el-form-item label="{{ __('panel/region.position') }}" prop="position">
          <el-input v-model="form.position" placeholder="{{ __('panel/region.position') }}"></el-input>
        </el-form-item>
        <el-form-item label="{{ __('panel/region.region_states') }}" prop="region_states">
          <table class="table table-bordered regions-table">
            <thead>
              <tr>
                <th width="40%">{{ __('panel/menu.countries') }}</th>
                <th width="40%">{{ __('panel/menu.states') }}</th>
                <th width="20%" class="text-end"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in form.region_states" :key="index">
                <td>
                  <select class="form-select form-select-sm country-select" v-model="item.country_id"
                          @change="getZones(item.country_id, index)" required>
                    <option v-for="country in countries" :key="country.id" :value="country.id">
                      @{{ country.name }}
                    </option>
                  </select>
                </td>
                <td>
                  <select class="form-select form-select-sm" v-model="item.state_id" required>
                    <option value="0">{{ __('panel/region.all_states') }}</option>
                    <option v-for="state in item.states" :key="state.id" :value="state.id">
                      @{{ state.name }}
                    </option>
                  </select>
                </td>
                <td class="text-end">
                  <el-button type="danger" @click="form.region_states.splice(index, 1)">
                    {{ __('panel/common.delete') }}
                  </el-button>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="3" class="text-end">
                  <el-button type="primary" @click="addRegionState">{{ __('panel/common.add') }}</el-button>
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
const { createApp, ref, reactive, onMounted, getCurrentInstance } = Vue;
const { ElMessageBox } = ElementPlus;

const listApp = createApp({
  setup() {
    const drawer = ref(false);
    const { proxy } = getCurrentInstance();
    const countries = ref([]);
    const states = ref([]);
    const deleteForm = ref(null);

    const form = reactive({
      id: 0,
      name: '',
      description: '',
      position: 0,
      region_states: []
    });

    const rules = {};

    onMounted(() => {
      getCountries();
    });

    const getCountries = async () => {
      try {
        const res = await axios.get('{{ front_route('countries.index') }}');
        countries.value = res.data;
        if (res.data.length > 0) {
          await getZones(res.data[0].id);
        }
      } catch (error) {
        console.error('Failed to get country list:', error);
      }
    };

    const getZones = async (countryId, index, id = null) => {
      try {
        const res = await axios.get('{{ front_route('countries.index') }}/' + countryId);
        let data = res.data.length ? res.data : [{ id: '', name: 'None' }];
        if (index !== undefined) {
          form.region_states[index].states = data;
          form.region_states[index].state_id = id !== null ? id : form.region_states[index].state_id;
        } else {
          states.value = data;
        }
      } catch (error) {
        console.error('Failed to get state list:', error);
      }
    };

    const edit = async (id) => {
      drawer.value = true;
      try {
        const res = await axios.get(`/{{ panel_name() }}/regions/${id}`);
        Object.keys(res).forEach(key => {
          if (form.hasOwnProperty(key)) form[key] = res[key];
        });
        form.region_states.forEach((item, index) => {
          getZones(item.country_id, index, item.state_id);
        });
      } catch (error) {
        console.error('Failed to get region details:', error);
      }
    };

    const addRegionState = () => {
      form.region_states.push({
        country_id: countries.value[0].id,
        state_id: 0,
        states: states.value
      });
    };

    const submit = async () => {
      const url = form.id ? `/{{ panel_name() }}/regions/${form.id}` : '{{ panel_route('regions.store') }}';
      const method = form.id ? 'put' : 'post';
      try {
        const res = await axios[method](url, form);
        drawer.value = false;
        inno.msg(res.message);
        window.location.reload();
      } catch (error) {
        inno.msg(error.response.data.message);
        console.error('Failed to submit form:', error);
      }
    };

    const close = () => proxy.$refs.formRef.resetFields();
    const create = () => (drawer.value = true);

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
        const deleteUrl = urls.base_url + '/regions/' + itemId;
        deleteForm.value.action = deleteUrl;
        deleteForm.value.submit();
      }).catch(() => {});
    };

    return {
      drawer, form, edit, rules, close, submit, create, getZones, countries, addRegionState, deleteForm, open
    };
  }
});

listApp.use(ElementPlus);
window.regionList = listApp.mount('#app');
</script>
@endpush
