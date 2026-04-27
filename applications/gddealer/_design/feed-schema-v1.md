# GunRack Dealer Feed Schema v1

This is the official feed format for GunRack dealer listings. Dealers host a feed at a URL we can reach (XML, JSON, or CSV), and our importer fetches it on a schedule based on your subscription tier.

You can paste a sample of your feed at https://gunrack.deals/dealers/feed-validator and we'll tell you what's wrong before the importer ever runs.

---

## Required fields per listing

| Field | Type | Notes |
|---|---|---|
| `upc` | 12 or 13 digit string | UPC-A or EAN-13. Dashes/spaces stripped automatically but it's better to send clean. |
| `category` | enum | One of: `firearm`, `ammo`, `part`, `accessory`, `optic`, `reloading`, `knife`, `apparel` |
| `price` | decimal | USD, your actual selling price. Greater than 0. |
| `condition` | enum | One of: `new`, `used`, `refurbished` |
| `url` | absolute URL | Must start with `https://`. Direct link to the product page on your site. |
| `free_shipping` | boolean | Use `1`/`0` or `true`/`false`. |
| `shipping_cost` | decimal | Required when `free_shipping` is not set. USD. Use `0` for free shipping if you prefer. |
| `in_stock` | boolean | Use `1`/`0`, `true`/`false`, `in_stock`/`out_of_stock`. |

## Optional fields

| Field | Type | Notes |
|---|---|---|
| `sku` | string, max 100 chars | Your internal SKU. Helps you reconcile listings with your inventory. |
| `map_price` | decimal | Manufacturer's MAP. **Must be greater than `price`** when present. When `map_price > price`, the listing displays MAP and shows a "Click to see price" CTA pointing to your `url`. |
| `stock_qty` | non-negative integer | If you can report exact quantity, do it; helps stock-low alerts. |

---

## Format examples

### XML

```xml
<?xml version="1.0" encoding="UTF-8"?>
<gunrack_feed xmlns="https://gunrack.deals/schema/feed/v1" version="1.0">
  <listings>
    <listing>
      <upc>012345678905</upc>
      <sku>GLK19-G5</sku>
      <category>firearm</category>
      <price>549.99</price>
      <map_price>599.99</map_price>
      <condition>new</condition>
      <url>https://example-dealer.com/products/glk19-g5</url>
      <shipping_cost>15.00</shipping_cost>
      <free_shipping>0</free_shipping>
      <in_stock>1</in_stock>
      <stock_qty>3</stock_qty>
    </listing>
    <listing>
      <upc>076683081124</upc>
      <category>ammo</category>
      <price>22.99</price>
      <condition>new</condition>
      <url>https://example-dealer.com/products/9mm-115gr-fmj</url>
      <shipping_cost>0.00</shipping_cost>
      <free_shipping>1</free_shipping>
      <in_stock>in_stock</in_stock>
      <stock_qty>120</stock_qty>
    </listing>
  </listings>
</gunrack_feed>
```

### JSON

Either a top-level array or wrapped in `{"listings": [...]}` works.

```json
{
  "listings": [
    {
      "upc": "012345678905",
      "sku": "GLK19-G5",
      "category": "firearm",
      "price": 549.99,
      "map_price": 599.99,
      "condition": "new",
      "url": "https://example-dealer.com/products/glk19-g5",
      "shipping_cost": 15.00,
      "free_shipping": false,
      "in_stock": true,
      "stock_qty": 3
    }
  ]
}
```

### CSV

First row must be the header. Column order does not matter.

```csv
upc,sku,category,price,map_price,condition,url,shipping_cost,free_shipping,in_stock,stock_qty
012345678905,GLK19-G5,firearm,549.99,599.99,new,https://example-dealer.com/products/glk19-g5,15.00,0,1,3
076683081124,,ammo,22.99,,new,https://example-dealer.com/products/9mm-115gr-fmj,0,1,1,120
```

---

## Validation behavior

The validator returns one of two outcomes per listing:

- **Error** - listing will be rejected on import. You must fix these.
- **Warning** - listing will be imported but flagged. Common warnings: ambiguous `in_stock` values, MAP not greater than price (MAP is ignored), duplicate UPCs (last one wins), non-digit characters in UPC (stripped).

The validator response shape:

```json
{
  "valid": true,
  "summary": {
    "total_records": 100,
    "valid_records": 98,
    "error_records": 2,
    "warning_records": 5
  },
  "errors": [
    { "row": 14, "upc": "012345678905", "field": "price", "message": "Price must be greater than 0 (got '0.00')." }
  ],
  "warnings": [
    { "row": 7, "upc": "076683081124", "field": "in_stock", "message": "Ambiguous in_stock value 'maybe'; will be treated as truthy on import." }
  ]
}
```

`valid` is `true` only when `errors` is empty. Warnings do not affect `valid`.
