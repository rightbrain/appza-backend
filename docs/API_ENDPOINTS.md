# API V1 Endpoints Reference

Comprehensive documentation for all API V1 endpoints in Appza Backend.

## Table of Contents

1. [Base URL & Middleware](#base-url--middleware)
2. [Authentication](#authentication)
3. [Lead Management](#lead-management)
4. [Theme Management](#theme-management)
5. [Page Components](#page-components)
6. [Global Configuration](#global-configuration)
7. [Free Trial](#free-trial)
8. [Firebase Integration](#firebase-integration)
9. [License Management](#license-management)
10. [Version Check](#version-check)
11. [App APIs](#app-apis)
12. [Build Management](#build-management)
13. [Plugin Management](#plugin-management)
14. [Error Responses](#error-responses)

---

## Base URL & Middleware

### Base URL

```
/appza/v1
```

### Middleware Applied

All V1 routes include the following middleware:

| Middleware | Description |
|-----------|-------------|
| `LogRequestResponse` | Logs API requests and responses (gated by `config('app.is_request_log')`) |
| `LogActivity` | Spatie activity log integration |
| `ApiVersionDeprecationMiddleware` | Returns deprecation warnings for sunset API versions |

---

## Authentication

All endpoints require authorization via product-specific hash headers. The appropriate header depends on the product being accessed.

### Headers

```http
appza-hash: {your_hash_token}
lazy-task-hash: {your_hash_token}
Fcom-mobile-hash: {your_hash_token}
```

Use the header that matches your product. The hash is generated when a Lead is created and returned in the lead creation response.

---

## Lead Management

### Create Lead

**Method:** `POST`

**URL:** `/appza/v1/lead/store/{product}`

**Description:** Create a new customer lead. Also automatically creates a free trial (7-day expiration + 14-day grace period) if one does not already exist for the given domain and product.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| product | string | Yes | Product type. Allowed values: `appza`, `lazy_task`, `fcom_mobile` |

#### Request Body

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "domain": "https://example.com",
  "note": "Interested in premium features"
}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| first_name | string | Yes | Lead's first name |
| last_name | string | Yes | Lead's last name |
| email | string | Yes | Lead's email address (must be valid email format) |
| domain | string | Yes | Lead's website URL (must be valid URL) |
| note | string | No | Additional notes about the lead |

#### Response Success (200)

```json
{
  "status": 200,
  "message": "Created Successfully",
  "data": {
    "appza_hash": "generated_hash_token",
    ...setup_data
  }
}
```

The hash key in the response varies by product:
- `appza` → `appza_hash`
- `fcom_mobile` → `fcom_mobile_hash`
- `lazy_task` → `lazy_task_hash`

#### Response Error (422)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "domain": ["The domain field is required."]
  }
}
```

#### Response Error (500)

```json
{
  "status": 500,
  "message": "Error description"
}
```

---

## Theme Management

### Get All Themes

**Method:** `GET`

**URL:** `/appza/v1/themes`

**Description:** Retrieve a list of all available themes filtered by plugin slug(s). Returns themes as a collection with styling and page preview data.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| plugin_slug | array | Yes | Array of plugin slugs to filter themes (e.g., `plugin_slug[]=appza`) |

#### Response Success (200)

```json
{
  "data": [
    {
      "id": 1,
      "name": "Modern Theme",
      "slug": "modern-theme",
      "plugin_slug": "appza",
      "created": "01-Jan-2026",
      "background_color": "#FFFFFF",
      "font_family": "Roboto",
      "text_color": "#000000",
      "font_size": 14,
      "is_transparent_background": false,
      "dashboard_page": "home",
      "login_page": null,
      "login_modal": null,
      "image_url": "https://cdn.../themes/1/image.png",
      "pages_preview": ["home", "category", "cart"],
      "default_active_page_slug": "home"
    }
  ]
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Theme ID |
| name | string | Theme display name |
| slug | string | Theme URL-safe identifier |
| plugin_slug | string | Associated plugin |
| created | string | Creation date in `d-M-Y` format |
| background_color | string | Theme background color hex code |
| font_family | string | Theme font family |
| text_color | string | Theme text color hex code |
| font_size | integer | Base font size |
| is_transparent_background | boolean | Whether background is transparent |
| dashboard_page | string | Default dashboard page slug |
| login_page | string/null | Login page slug |
| login_modal | string/null | Login modal configuration |
| image_url | string | Theme preview image URL |
| pages_preview | array | List of page slugs included in theme |
| default_active_page_slug | string | Default active page |

---

### Get Single Theme

**Method:** `GET`

**URL:** `/appza/v1/themes/get-theme`

**Description:** Get detailed information about a specific theme including global configs (appbar, navbar, drawer), pages with their components, styling properties, and all nested relationships.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| slug | string | Yes | Theme slug identifier |
| plugin_slug | string | Yes | Plugin slug |

#### Response Success (200)

```json
{
  "data": {
    "theme_name": "Modern Theme",
    "theme_slug": "modern-theme",
    "plugin_slug": "appza",
    "background_color": "#FFFFFF",
    "font_family": "Roboto",
    "text_color": "#000000",
    "font_size": 14,
    "is_transparent_background": false,
    "dashboard_page": "home",
    "login_page": null,
    "login_modal": null,
    "global_config": {
      "navbar": {
        "unique_id": "md5_hash",
        "id": 1,
        "mode": "navbar",
        "name": "Bottom NavBar",
        "slug": "bottom-navbar",
        "properties": {
          "padding_left": "0",
          "padding_right": "0",
          "background_color": "#FFFFFF",
          "...50+ styling fields"
        },
        "components": []
      },
      "appbar": { "..." },
      "drawer": { "..." }
    },
    "pages": [
      {
        "unique_id": "md5_hash",
        "name": "Home",
        "slug": "home",
        "is_persistent_footer_button": false,
        "persistent_footer_buttons": null,
        "components": [
          {
            "unique_id": "md5_hash",
            "name": "Component Name",
            "slug": "component-slug",
            "is_upcoming": false,
            "properties": {},
            "customize_properties": {},
            "styles": {}
          }
        ]
      }
    ]
  }
}
```

---

## Page Components

### Get Page Components

**Method:** `GET`

**URL:** `/appza/v1/page-component`

**Description:** Retrieve available components for a specific page, grouped by component type. Each component includes its properties, styling, and customization options.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page_slug | string | Yes | Page identifier slug |
| plugin_slug | array | Yes | Array of plugin slugs (e.g., `plugin_slug[]=appza`) |

#### Response Success (200)

```json
{
  "data": [
    {
      "name": "Group Name",
      "icon": "icon_code",
      "items": [
        {
          "page_id": null,
          "unique_id": "random_md5_hash",
          "name": "Product Grid",
          "slug": "product-grid",
          "is_upcoming": false,
          "properties": {
            "label": "Product Grid",
            "group_name": "Ecommerce",
            "layout_type": "grid",
            "icon_code": "0xe123",
            "event": "load_products",
            "scope": "page",
            "class_type": "product",
            "is_multiple": true,
            "selected_design": 0,
            "detailsPage": null,
            "is_transparent_background": false
          },
          "customize_properties": {},
          "styles": {}
        }
      ]
    }
  ]
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| name | string | Component group name |
| icon | string | Group icon code |
| items | array | Array of components in this group |
| items[].unique_id | string | Unique MD5 identifier for the component instance |
| items[].name | string | Component display name |
| items[].slug | string | Component slug |
| items[].is_upcoming | boolean | Whether component is coming soon |
| items[].properties | object | Component properties (label, layout, events, etc.) |
| items[].styles | object | Component styling data |

---

## Global Configuration

### Get Global Config

**Method:** `GET`

**URL:** `/appza/v1/global-config`

**Description:** Retrieve global configuration items (appbar, navbar, drawer) with their nested components and full styling properties (50+ fields including padding, margin, shadow, colors, etc.).

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| mode | string | Yes | Config mode: `appbar`, `navbar`, or `drawer` |
| plugin_slug | array | Yes | Array of plugin slugs (e.g., `plugin_slug[]=appza`) |

#### Response Success (200)

```json
{
  "data": [
    {
      "unique_id": "md5_hash",
      "id": 1,
      "mode": "navbar",
      "name": "Bottom NavBar",
      "slug": "bottom-navbar",
      "plugin_slug": "appza",
      "image_url": "https://cdn.../config/image.png",
      "is_active": true,
      "is_upcoming": false,
      "properties": {
        "padding_left": "0",
        "padding_right": "0",
        "padding_top": "0",
        "padding_bottom": "0",
        "margin_left": "0",
        "margin_right": "0",
        "background_color": "#FFFFFF",
        "shadow_color": "#000000",
        "icon_active_color": "#2196F3",
        "icon_inactive_color": "#9E9E9E",
        "text_active_color": "#2196F3",
        "text_inactive_color": "#9E9E9E"
      },
      "components": [
        {
          "unique_id": "md5_hash",
          "name": "Home Icon",
          "slug": "home-icon",
          "properties": {},
          "styles": {}
        }
      ]
    }
  ]
}
```

---

## Free Trial

### Activate Free Trial

**Method:** `POST`

**URL:** `/appza/v1/free/trial/{product}`

**Description:** Activate a free trial for a website. Creates a trial with 7-day expiration and 14-day grace period. Prevents duplicate trials using database locking.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| product | string | Yes | Product type. Allowed values: `appza`, `lazy_task`, `fcom_mobile` |

#### Request Body

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "site_url": "https://example.com",
  "plugin_slug": "appza"
}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| name | string | Yes | User's name |
| email | string | Yes | User's email (must be valid email format) |
| site_url | string | Yes | Website URL (must be valid URL) |
| plugin_slug | string | Yes | Plugin slug identifier |

#### Response Success (200)

```json
{
  "status": 200,
  "url": "/appza/v1/free/trial/appza",
  "method": "POST",
  "message": "Created Successfully",
  "data": {
    "addon_name": "Appza Plugin",
    "version": "1.0.0",
    "download_url": "https://cdn.../plugin.zip"
  }
}
```

#### Response Error (422)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "site_url": ["The site url field is required."],
    "email": ["The email must be a valid email address."]
  }
}
```

---

## Firebase Integration

### Get Firebase Credentials

**Method:** `GET`

**URL:** `/appza/v1/firebase/credential/{product}`

**Description:** Retrieve Firebase configuration credentials for a specific product. Requires authorization via product hash header.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| product | string | Yes | Product slug. Allowed values: `appza`, `lazy_task`, `fcom_mobile` |

#### Response Success (200)

```json
{
  "status": 200,
  "data": {
    "api_key": "AIzaSy...",
    "auth_domain": "project.firebaseapp.com",
    "project_id": "project-id",
    "storage_bucket": "project.appspot.com",
    "messaging_sender_id": "123456789",
    "app_id": "1:123456789:web:abcdef"
  }
}
```

#### Response Error (404)

```json
{
  "status": 404,
  "message": "Firebase credentials not found"
}
```

---

## License Management

### Check License (Web)

**Method:** `GET`

**URL:** `/appza/v1/license/check`

**Description:** Check if a license key is valid for a given site URL. Validates against the Fluent external license provider.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| site_url | string | Yes | Website URL |
| license_key | string | Yes | License key to validate |

#### Response Success (200)

```json
{
  "status": 200,
  "message": "License is valid",
  "data": {
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "site_url": "https://example.com",
    "product_title": "Appza Premium",
    "activation_limit": 1,
    "activations_count": 1,
    "expiration_date": "2027-01-01T00:00:00.000000Z",
    "is_active": true
  }
}
```

#### Response Error (404)

```json
{
  "status": 404,
  "message": "License not found"
}
```

---

### Activate License

**Method:** `POST`

**URL:** `/appza/v1/license/activate`

**Description:** Activate a premium license for a website. Validates free trial status, calls the Fluent API for activation, and updates BuildDomain and FluentLicenseInfo records.

#### Request Body

```json
{
  "site_url": "https://example.com",
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "email": "user@example.com"
}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| site_url | string | Yes | Website URL to activate |
| license_key | string | Yes | License key for activation |
| email | string | No | User's email address |

#### Response Success (200)

```json
{
  "status": 200,
  "message": "License activated successfully",
  "data": {
    "activation_hash": "generated_hash",
    "site_url": "https://example.com",
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "product_id": 123,
    "product_title": "Appza Premium",
    "activation_limit": 1,
    "activations_count": 1,
    "expiration_date": "2027-01-01T00:00:00.000000Z"
  }
}
```

#### Response Error (422)

```json
{
  "status": 422,
  "message": "License activation failed",
  "errors": {
    "license_key": ["Invalid license key"],
    "site_url": ["This site is already activated"]
  }
}
```

---

### Deactivate License

**Method:** `GET`

**URL:** `/appza/v1/license/deactivate`

**Description:** Deactivate a license or mark a plugin as deleted for a given site.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| site_url | string | Yes | Website URL (must be valid URL) |
| product | string | Yes | Product slug. Allowed values: `appza`, `lazy_task`, `fcom_mobile` |
| appza_action | string | Yes | Action to perform. Allowed values: `license_deactivate`, `plugin_delete` |
| license_key | string | Conditional | Required if `appza_action` is `license_deactivate` |

#### Response Success (200)

```json
{
  "status": 200,
  "message": "License deactivated successfully"
}
```

---

### License Version Check

**Method:** `POST`

**URL:** `/appza/v1/license/version/check`

**Description:** Get the latest version information for a licensed product from the Fluent API.

#### Request Body

```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX"
}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| license_key | string | Yes | License key to check version for |

#### Response Success (200)

```json
{
  "status": 200,
  "data": {
    "latest_version": "1.2.0",
    "download_url": "https://cdn.../plugin-v1.2.0.zip",
    "changelog": "Bug fixes and improvements",
    "requires_update": true
  }
}
```

---

## Version Check

### Version Check (Plugin)

**Method:** `GET`

**URL:** `/appza/v1/version-check`

**Description:** Check the latest mobile and plugin versions for compatibility mapping. Returns compatible version pairs between mobile app and plugin.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| app_name | string | Yes | Application name |
| mobile_version | string | Yes | Current mobile version (semantic versioning format: `x.y.z`) |
| plugin_version | string | Yes | Current plugin version (semantic versioning format: `x.y.z`) |
| plugin_slug | string | Yes | Plugin slug identifier |

#### Response Success (200)

```json
{
  "status": 200,
  "latest_mobile": "2.0.0",
  "latest_plugin": "1.5.0",
  "compitable_mobile_version_vs_plugin": ["1.4.0", "1.5.0"],
  "compitable_plugin_version_vs_mobile": ["1.9.0", "2.0.0"]
}
```

#### Response Error (404)

```json
{
  "status": 404,
  "message": "App not found"
}
```

---

## App APIs

### App License Check

**Method:** `GET`

**URL:** `/appza/v1/app/license-check`

**Description:** Check license status for the mobile app. Checks free trial validity first, then premium license via Fluent API with caching. Returns license type, status, and active popup messages.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| site_url | string | Yes | Website URL (must be valid URL) |
| product | string | Yes | Product slug. Allowed values: `appza`, `lazy_task`, `fcom_mobile` |

#### Response Success - Free Trial (200)

```json
{
  "status": "ok",
  "license_type": "free_trial",
  "message": "Free trial is active",
  "data": {
    "site_url": "https://example.com",
    "product": "appza",
    "grace_period_date": "2026-01-15T00:00:00.000000Z",
    "days_remaining": 14
  },
  "popup_message": []
}
```

#### Response Success - Premium (200)

```json
{
  "status": "ok",
  "license_type": "premium",
  "message": "Premium license is active",
  "data": {
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "site_url": "https://example.com",
    "product_title": "Appza Premium",
    "expiration_date": "2027-01-01T00:00:00.000000Z"
  },
  "popup_message": [
    {
      "title": "New Feature",
      "message": "Check out our latest update!",
      "type": "info"
    }
  ]
}
```

#### Response Error (403)

```json
{
  "status": "error",
  "license_type": "expired",
  "message": "License has expired"
}
```

---

### Mobile Version Check

**Method:** `GET`

**URL:** `/appza/v1/app/version-check`

**Description:** Check if a mobile app version requires an update. Returns update status based on version comparison with the registered app.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| app_name | string | Yes | Application name |
| mobile_version | string | Yes | Current mobile version (semantic versioning format: `x.y.z`) |
| plugin_version | string | Yes | Current plugin version (semantic versioning format: `x.y.z`) |

#### Response Success - Up to Date (200)

```json
{
  "status": "ok",
  "message": "App is up to date"
}
```

#### Response - Optional Update (200)

```json
{
  "status": "optional_update",
  "message": "A new version is available",
  "latest_mobile": "2.0.0"
}
```

#### Response - Force Update (200)

```json
{
  "status": "force_update",
  "message": "Please update to the latest version",
  "latest_mobile": "2.0.0"
}
```

#### Response Error (422)

```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "app_name": ["The app name field is required."],
    "mobile_version": ["The mobile version format is invalid."]
  }
}
```

---

## Build Management

### Create Build Resource

**Method:** `POST`

**URL:** `/appza/v1/build/resource`

**Description:** Upload build resources (logo, splash screen) and configure platform selection. Validates license via Fluent API, uploads files to Cloudflare R2, and saves platform configuration to BuildDomain.

#### Request Body

```json
{
  "app_name": "My App",
  "app_logo": "base64_or_url_encoded_image",
  "app_splash_screen_image": "base64_or_url_encoded_image",
  "site_url": "https://example.com",
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "email": "user@example.com",
  "plugin_slug": "appza",
  "platform": ["android", "ios"]
}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| app_name | string | Conditional | Required if Android platform is selected |
| app_logo | string | Yes | App logo image (base64 or URL) |
| app_splash_screen_image | string | Yes | Splash screen image (base64 or URL) |
| site_url | string | Yes | Website URL (must be valid URL) |
| license_key | string | Yes | License key for validation |
| email | string | Yes | User's email address (must be valid email) |
| plugin_slug | string | Yes | Plugin slug identifier |
| platform | array | Yes | Target platforms. Values: `android`, `ios`, or both |
| ios_issuer_id | string | Conditional | Required if any iOS field is provided |
| ios_key_id | string | Conditional | Required if any iOS field is provided |
| ios_p8_file_content | string | Conditional | Base64 encoded .p8 key content. Required if any iOS field is provided |
| ios_team_id | string | Conditional | Required if any iOS field is provided |

#### Response Success (200)

```json
{
  "status": 200,
  "message": "Build resources created successfully",
  "data": {
    "package_name": "com.example.app",
    "bundle_name": "com.example.app"
  }
}
```

---

### iOS Keys Verify

**Method:** `POST`

**URL:** `/appza/v1/build/ios-keys-verify`

**Description:** Upload and verify iOS signing certificate (.p8 key). Uploads the key file to Cloudflare R2 and validates it via IosBuildValidationService against Apple services.

#### Request Body

```json
{
  "site_url": "https://example.com",
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "ios_issuer_id": "xxxx-xxxx-xxxx",
  "ios_key_id": "XXXXXXXXXX",
  "ios_p8_file_content": "base64_encoded_p8_key_content",
  "ios_team_id": "XXXXXXXXXX"
}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| site_url | string | Yes | Website URL |
| license_key | string | Yes | License key for validation |
| ios_issuer_id | string | Yes | Apple App Store Connect Issuer ID |
| ios_key_id | string | Yes | Apple API Key ID |
| ios_p8_file_content | string | Yes | Base64 encoded .p8 private key content |
| ios_team_id | string | Yes | Apple Developer Team ID |

#### Response Success (200)

```json
{
  "status": 200,
  "message": "iOS keys verified successfully",
  "data": {
    "valid": true,
    "bundle_name": "com.example.app"
  }
}
```

#### Response Error (422)

```json
{
  "status": 422,
  "message": "iOS key verification failed",
  "errors": {
    "ios_p8_file_content": ["Invalid .p8 key file"]
  }
}
```

---

### iOS Check App Name

**Method:** `POST`

**URL:** `/appza/v1/build/ios-check-app-name`

**Description:** Validate iOS app name availability with Apple services.

#### Request Body

```json
{
  "site_url": "https://example.com",
  "license_key": "XXXX-XXXX-XXXX-XXXX"
}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| site_url | string | Yes | Website URL (must be valid URL) |
| license_key | string | Yes | License key for validation |

#### Response Success (200)

```json
{
  "status": 200,
  "data": {
    "available": true,
    "app_name": "My App"
  }
}
```

---

### Push Notification Resource

**Method:** `POST`

**URL:** `/appza/v1/build/push-notification-resource`

**Description:** Upload push notification configuration files (FCM JSON for Android, APNs plist for iOS). Files are uploaded to Cloudflare R2 and old files are automatically deleted.

#### Request Body

```json
{
  "site_url": "https://example.com",
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "android_notification_content": "{fcm_json_content}",
  "ios_notification_content": "{apns_plist_content}"
}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| site_url | string | Yes | Website URL (must be valid URL) |
| license_key | string | Yes | License key for validation |
| android_notification_content | string | Conditional | FCM JSON configuration. Required if `ios_notification_content` is not provided |
| ios_notification_content | string | Conditional | APNs plist configuration. Required if `android_notification_content` is not provided |

#### Response Success (200)

```json
{
  "status": 200,
  "message": "Push notification configured successfully"
}
```

---

### Create Build

**Method:** `POST`

**URL:** `/appza/v1/build`

**Description:** Initiate a new APK/IPA build. Creates a BuildDomainHistory record, dispatches ProcessBuild jobs per platform, and sends email notification. The build is processed asynchronously via a queued job.

#### Request Body

```json
{
  "site_url": "https://example.com",
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "is_push_notification": true
}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| site_url | string | Yes | Website URL (must be valid URL) |
| license_key | string | Yes | License key for validation |
| is_push_notification | boolean | No | Whether to include push notification in the build |

#### Response Success (200)

```json
{
  "status": 200,
  "message": "Build started successfully",
  "data": {
    "id": 123,
    "version_id": 1,
    "build_domain_id": 45,
    "app_name": "My App",
    "ios_app_name": "My iOS App",
    "build_id": 678
  }
}
```

#### Response Error - Locked (423)

```json
{
  "status": "locked",
  "message": "A build is already in progress"
}
```

#### Response Error - Unauthorized (401)

```json
{
  "status": "unauthorized",
  "message": "License validation failed"
}
```

#### Response Error - Not Found (404)

```json
{
  "status": "not_found",
  "message": "Build domain not found"
}
```

---

### Get Build History

**Method:** `GET`

**URL:** `/appza/v1/build/history`

**Description:** Retrieve build history for a site. Returns a list of all builds with their status, download URLs, and timestamps.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| site_url | string | Yes | Website URL |
| license_key | string | Yes | License key for validation |

#### Response Success (200)

```json
{
  "status": 200,
  "data": [
    {
      "app_name": "My App",
      "build_target": "android",
      "status": "Completed",
      "created_date": "12-Mar-2026",
      "created_time": "10:30:00 AM",
      "apk_name": "my-app.apk",
      "aab_name": "my-app.aab",
      "apk_url": "https://cdn.../builds/my-app.apk",
      "aab_url": "https://cdn.../builds/my-app.aab"
    },
    {
      "app_name": "My App",
      "build_target": "ios",
      "status": "Completed",
      "created_date": "12-Mar-2026",
      "created_time": "10:30:00 AM"
    }
  ]
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| app_name | string | Application name |
| build_target | string | Build platform: `android` or `ios` |
| status | string | Build status (Pending, Processing, Completed, Failed, Delete) |
| created_date | string | Creation date in `d-M-Y` format |
| created_time | string | Creation time in `H:i:s A` format |
| apk_name | string | APK filename (Android only) |
| aab_name | string | AAB filename (Android only) |
| apk_url | string | APK download URL (Android only) |
| aab_url | string | AAB download URL (Android only) |

---

### Build Response (Builder Callback)

**Method:** `POST`

**URL:** `/appza/v1/build/response/{id}`

**Description:** Callback endpoint for the builder application to report build completion or failure. Updates build status and sends completion/failure email notifications.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Build Order ID |

#### Request Body

```json
{
  "build_message": "Build completed successfully"
}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| build_message | string | No | Build status message or error details |

#### Response Success (200)

```json
{
  "status": 200,
  "message": "Build status updated"
}
```

---

### Build Process Start (Builder Callback)

**Method:** `POST`

**URL:** `/appza/v1/build/process-start/{id}`

**Description:** Mark a build process as started. Records the process start timestamp on the build order.

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | Build Order ID |

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| build_message | string | No | Optional status message |

#### Response Success (200)

```json
{
  "status": 200,
  "message": "Build process started"
}
```

---

## Plugin Management

### Get All Plugins

**Method:** `GET`

**URL:** `/appza/v1/plugins`

**Description:** Retrieve a list of all available plugins/addons.

#### Response Success (200)

```json
{
  "data": [
    {
      "id": 1,
      "name": "Payment Gateway",
      "slug": "payment-gateway",
      "prefix": "pg",
      "title": "Payment Gateway Plugin",
      "description": "Payment integration for mobile apps",
      "others": null,
      "created": "01-Jan-2026",
      "is_disable": false,
      "image": "https://cdn.../plugins/payment-gateway.png"
    }
  ]
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Plugin ID |
| name | string | Plugin name |
| slug | string | Plugin slug identifier |
| prefix | string | Plugin prefix |
| title | string | Plugin display title |
| description | string | Plugin description |
| others | mixed | Additional plugin data |
| created | string | Creation date in `d-M-Y` format |
| is_disable | boolean | Whether the plugin is disabled |
| image | string | Plugin image URL |

---

### Check Disabled Plugin

**Method:** `GET`

**URL:** `/appza/v1/plugin/check-disable`

**Description:** Check if a specific plugin is disabled.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| plugin_slug | string | Yes | Plugin slug to check |

#### Response Success (200)

```json
{
  "status": 200,
  "is_disable": false
}
```

#### Response Error (404)

```json
{
  "status": "not_found",
  "message": "Plugin not found"
}
```

---

### Plugin Install Latest Version

**Method:** `GET`

**URL:** `/appza/v1/plugin/install-latest-version`

**Description:** Get the latest version and download information for a plugin addon.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| plugin_slug | string | Yes | Plugin slug. Allowed values: `fcom-mobile`, `lazytasks-premium`, `lazytasks-whiteboard` |

#### Response Success (200)

```json
{
  "status": 200,
  "data": {
    "version": "1.2.0",
    "download_url": "https://cdn.../plugin-v1.2.0.zip",
    "changelog": "New features and bug fixes"
  }
}
```

---

### Plugin Version Check

**Method:** `GET`

**URL:** `/appza/v1/plugin/version-check`

**Description:** Check for plugin updates by comparing the installed version against the latest available version.

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| installed_version | string | Yes | Currently installed plugin version |
| plugin_slug | string | Yes | Plugin slug to check |

#### Response Success (200)

```json
{
  "status": 200,
  "data": {
    "latest_version": "1.2.0",
    "current_version": "1.0.0",
    "update_available": true,
    "download_url": "https://cdn.../plugin-v1.2.0.zip"
  }
}
```

---

## Error Responses

### Common Error Codes

#### 401 Unauthorized

```json
{
  "status": 401,
  "message": "Unauthorized access"
}
```

#### 404 Not Found

```json
{
  "status": 404,
  "message": "Resource not found"
}
```

#### 410 Gone (Deprecated API Version)

```json
{
  "status": 410,
  "message": "This API version is deprecated"
}
```

#### 422 Validation Error

```json
{
  "status": 422,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

#### 500 Server Error

```json
{
  "status": 500,
  "message": "Internal server error"
}
```

---

## Build Flow Summary

The build process follows a multi-step flow:

1. **POST** `/build/resource` — Validate license, upload logo/splash to R2, save platform selection
2. **POST** `/build/ios-keys-verify` — (iOS only) Upload .p8 key, validate via Apple services
3. **POST** `/build/ios-check-app-name` — (iOS only) Validate app name with Apple
4. **POST** `/build/push-notification-resource` — (Optional) Upload FCM JSON / APNs plist
5. **POST** `/build` — Create BuildDomainHistory, dispatch ProcessBuild job, send email
6. **GET** `/build/history` — Poll build status
7. **POST** `/build/process-start/{id}` — Builder marks process as started (builder callback)
8. **POST** `/build/response/{id}` — Builder reports completion/failure (builder callback)

---

**Last Updated**: March 2026
**API Version**: v1
**Base URL**: `/appza/v1`
