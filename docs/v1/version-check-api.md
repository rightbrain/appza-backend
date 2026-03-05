# Version Check API

Check mobile app and plugin version compatibility.

## Endpoint

```
GET /api/appza/v1/version-check
```

## Query Parameters

| Parameter        | Type   | Required | Description                                      | Example                                                                  |
|------------------|--------|----------|--------------------------------------------------|--------------------------------------------------------------------------|
| `app_name`       | string | Yes      | Mobile app slug (from `appza_support_mobile_apps`) | `appza-wp,appza-woo,appza-fluent-community,fluent-community-stand-alone` |
| `mobile_version` | string | Yes      | Current mobile app version (format: `X.Y.Z`)     | `1.0.151`                                                                |
| `plugin_version` | string | Yes      | Current plugin version (format: `X.Y.Z`)         | `1.2.201`                                                                |
| `plugin_slug`    | string | Yes      | Plugin identifier (addon_slug)                    | `fcom-mobile,lazytasks-premium,lazytasks-whiteboard`                                         |

## Example Request

```
GET /api/appza/v1/version-check?app_name=fluent-community-stand-alone&mobile_version=1.0.151&plugin_version=1.2.201&plugin_slug=fcon_mobile
```

## Success Response (200)

```json
{
  "status": 200,
  "latest_mobile": "1.0.200",
  "latest_plugin": "2.0.0",
  "compitable_mobile_version_vs_plugin": [
    "1.0.100",
    "1.0.151"
  ],
  "compitable_plugin_version_vs_mobile": [
    "1.2.100",
    "1.2.201",
    "1.2.300"
  ]
}
```

### Response Fields

| Field                                | Type     | Description                                                                 |
|--------------------------------------|----------|-----------------------------------------------------------------------------|
| `status`                             | integer  | HTTP status code                                                            |
| `latest_mobile`                      | string   | Latest available mobile app version                                         |
| `latest_plugin`                      | string   | Latest plugin version supported by the latest mobile version                |
| `compitable_mobile_version_vs_plugin`| array    | Mobile versions that support the given `plugin_version`                     |
| `compitable_plugin_version_vs_mobile`| array    | Plugin versions compatible with the given `mobile_version`                  |

## Error Responses

### Validation Error (422)

Returned when required parameters are missing or have invalid format.

```json
{
  "message": "The app name field is required.",
  "errors": {
    "app_name": ["The app name field is required."]
  }
}
```

### App Not Found (404)

```json
{
  "status": 404,
  "message": "Mobile app not found."
}
```

### Server Error (500)

```json
{
  "status": 500,
  "message": "An internal server error occurred."
}
```
