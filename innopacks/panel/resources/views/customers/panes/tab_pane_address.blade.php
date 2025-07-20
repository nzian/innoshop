<div class="tab-pane fade" id="address-tab-pane" role="tabpanel" tabindex="0">
  <div class="d-flex justify-content-end mb-3">
    <button class="btn btn-sm add-address btn-outline-primary">{{ __('panel/common.add') }}</button>
  </div>
  <table class="table align-middle rounded border">
    <thead>
    <tr>
      <th>{{ __('panel/common.id') }}</th>
      <th>{{ __('common/address.name') }}</th>
      <th>{{ __('common/address.address') }}</th>
      <th>{{ __('common/address.phone') }}</th>
      <th>{{ __('panel/common.created_at') }}</th>
      <th class="text-end"></th>
    </tr>
    </thead>
    <tbody>
    @foreach ($addresses as $address)
      <tr data-id="{{ $address['id'] }}">
        <td>{{ $address['id'] }}</td>
        <td>{{ $address['name'] }}</td>
        <td>{{ $address['address_1'] }}</td>
        <td>{{ $address['phone'] }}</td>
        <td>{{ $address['created_at'] }}</td>
        <td class="text-end">
  <div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
            id="dropdownMenuButton{{ $item->id }}" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="bi bi-three-dots-vertical"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton{{ $item->id }}">
      <!-- Edit -->
      <li>
        <a class="dropdown-item edit-address" href="javascript:void(0)">
          <i class="bi bi-pencil-square"></i> {{ __('panel/common.edit') }}
        </a>
      </li>
      <!-- Delete -->
      <li>
        <a class="dropdown-item text-danger delete-address" href="javascript:void(0)">
          <i class="bi bi-trash"></i> {{ __('panel/common.delete') }}
        </a>
      </li>
    </ul>
  </div>
</td>

      </tr>
    @endforeach
    </tbody>
  </table>
</div> 