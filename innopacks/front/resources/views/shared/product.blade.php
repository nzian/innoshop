@if($product->fallbackName())
<div class="product-grid-item {{ request('style_list') ?? '' }} card " style="height:300px; border-radius: 16px !important;">

  <div class="image position-relative border-radius-top-16">
    @hookinsert('product.list_item.image.before')
    <a href="{{ $product->url }}">
      <img src="{{ $product->image_url }}" class="img-fluid">
    </a>
    <div class="wishlist-container add-wishlist" data-in-wishlist="{{ $product->hasFavorite() }}"
      data-id="{{ $product->id }}" data-price="{{ $product->masterSku->price }}">
      <i class="bi bi-heart{{ $product->hasFavorite() ? '-fill' : '' }}"></i>
    </div>
  </div>
  <div class="product-item-info px-2 mb-3">
    <div class="product-name">
      <a href="{{ $product->url }}" data-bs-toggle="tooltip" title="{{ $product->fallbackName() }}"
        data-placement="top">
        {{ $product->fallbackName() }}
      </a>
    </div>

    @hookinsert('product.list_item.name.after')

    @if(request('style_list') == 'list')
    <div class="sub-product-title">{{ $product->fallbackName('summary') }}</div>
    @endif

    <div class="product-bottom">
      @if(!system_setting('disable_online_order'))
      <div class="product-bottom-btns">
        <div class="btn-add-cart cursor-pointer" data-id="{{ $product->id }}"
          data-price="{{ $product->masterSku->getFinalPrice() }}"
          data-sku-id="{{ $product->masterSku->id }}">
          <button class="btn btn-primary add-cart">
            <i class="bi bi-cart-plus"></i>
          </button>
        </div>
      </div>
      @endif
      <div class="product-price d-flex justify-content-between align-items-center">
        <div class="price-new">{{ $product->masterSku->getFinalPriceFormat() }}</div>
        @if ($product->masterSku->origin_price)
        <div class="price-old"><s>{{ $product->masterSku->origin_price_format }}</s></div>
        @endif
      </div>
    </div>
    @if(request('style_list') == 'list')
    <div class="add-wishlist" data-in-wishlist="{{ $product->hasFavorite() }}" data-id="{{ $product->id }}"
      data-price="{{ $product->masterSku->price }}">
      <i class="bi bi-heart{{ $product->hasFavorite() ? '-fill' : '' }}"></i> {{ __('front/product.add_wishlist') }}
    </div>
    @endif
  </div>
</div>
@endif