# Build API V1 - Frontend Developer Documentation

**Base URL:** `/appza/v1/build`

**Authentication:** All requests require authorization via the `Lead` authorization header. Unauthorized requests return `401`.

**Common Response Format:**
```json
{
  "status": 200,
  "url": "https://example.com/appza/v1/build/...",
  "method": "POST",
  "message": "Response message"
}
```

---

## 1. Build Resource (Android/iOS Setup)

Validates the license, uploads app logo & splash screen, and confirms build resource selection.

**Endpoint:** `POST /appza/v1/build/resource`

### Request Body

| Field                    | Type     | Required                        | Description                          |
|--------------------------|----------|---------------------------------|--------------------------------------|
| `app_name`               | string   | Required if platform has `android` | App display name (Android)          |
| `app_logo`               | string   | Yes                             | URL of the app logo image            |
| `app_splash_screen_image`| string   | Yes                             | URL of the splash screen image       |
| `site_url`               | string   | Yes                             | Valid URL of the website             |
| `license_key`            | string   | Yes                             | License key                          |
| `email`                  | string   | Yes                             | Confirmation email (valid email)     |
| `plugin_slug`            | string   | Yes                             | Plugin slug identifier               |
| `platform`               | array    | Yes                             | Array of platforms: `["android"]`, `["ios"]`, or `["android", "ios"]` |
| `ios_issuer_id`          | string   | Required if any iOS field is set | Apple Issuer ID                     |
| `ios_key_id`             | string   | Required if any iOS field is set | Apple Key ID                        |
| `ios_p8_file_content`    | string   | Required if any iOS field is set | Content of the .p8 key file         |
| `ios_team_id`            | string   | Required if any iOS field is set | Apple Team ID                       |

### Success Response (200)
```json
{
  "status": 200,
  "url": "...",
  "method": "POST",
  "message": "App selection for build requests is confirmed.",
  "data": {
    "package_name": "com.example.app",
    "bundle_name": "com.example.app"
  }
}
```

### Error Responses

| Status | Message |
|--------|---------|
| 401    | Unauthorized. |
| 404    | Domain not found. / Item id not found. / License record not found for this site. |
| 422    | Fluent license configuration is missing. / License validation messages |
| 423    | Build process off for lazy task. |
| 400    | App logo/splash image invalid or cannot be downloaded. |
| 503    | Could not connect to the license server. |

---

## 2. iOS Keys Verify

Uploads iOS .p8 key file content and validates iOS build configuration.

**Endpoint:** `POST /appza/v1/build/ios-keys-verify`

### Request Body

| Field                | Type   | Required | Description              |
|----------------------|--------|----------|--------------------------|
| `site_url`           | string | Yes      | Website URL              |
| `license_key`        | string | Yes      | License key              |
| `ios_issuer_id`      | string | Yes      | Apple Issuer ID          |
| `ios_key_id`         | string | Yes      | Apple Key ID             |
| `ios_p8_file_content`| string | Yes      | Content of the .p8 file  |
| `ios_team_id`        | string | Yes      | Apple Team ID            |

### Success Response (200)
```json
{
  "status": 200,
  "url": "...",
  "method": "POST",
  "message": "Validation success message",
  "data": {
    "package_name": "com.example.app",
    "bundle_name": "com.example.app"
  }
}
```

### Error Responses

| Status | Message |
|--------|---------|
| 404    | Domain or license key is incorrect |
| varies | iOS validation failure messages |

---

## 3. iOS Check App Name

Checks and sets the iOS app name via Apple's validation service.

**Endpoint:** `POST /appza/v1/build/ios-check-app-name`

### Request Body

| Field        | Type   | Required | Description  |
|--------------|--------|----------|--------------|
| `site_url`   | string | Yes      | Valid URL    |
| `license_key`| string | Yes      | License key  |

### Success Response (200)
```json
{
  "status": 200,
  "url": "...",
  "method": "POST",
  "message": "Success message",
  "data": {
    "package_name": "com.example.app",
    "bundle_name": "com.example.app",
    "ios_app_name": "My App"
  }
}
```

### Error Response
Returns same structure with `ios_app_name: null` and validation failure message.

---

## 4. Start Build

Triggers the actual APK/IPA build process. Creates build history and dispatches the build job.

**Endpoint:** `POST /appza/v1/build`

### Request Body

| Field                  | Type    | Required | Description                          |
|------------------------|---------|----------|--------------------------------------|
| `site_url`             | string  | Yes      | Valid URL                            |
| `license_key`          | string  | Yes      | License key                          |
| `is_push_notification` | boolean | No       | Enable push notification in the build (default: `false`) |

### Success Response (200)
```json
{
  "status": 200,
  "url": "...",
  "method": "POST",
  "message": "Your App building process has been started successfully.",
  "data": {
    "id": 1,
    "version_id": 5,
    "build_domain_id": 10,
    "app_name": "My App",
    "ios_app_name": "My iOS App",
    "build_id": 42
  }
}
```

> **Note:** `ios_app_name` and `build_id` are included only when applicable.

### Error Responses

| Status | Message |
|--------|---------|
| 401    | Unauthorized |
| 404    | Domain or license key wrong / Plugin slug missing / Builder does not support this plugin / Build process off for lazy task |
| 409    | An app building process is already going on. Please try again later. (iOS concurrent build lock) |
| 423    | Builder is busy / License check url config error / Push notification file missing |
| 500    | Failed to create build. Error: ... |

### Supported Plugin Slugs
- `woocommerce`
- `tutor-lms`
- `wordpress`
- `fluent-community`

---

## 5. Build History

Returns the list of all build orders for a given domain.

**Endpoint:** `GET /appza/v1/build/history`

### Query Parameters

| Param        | Type   | Required | Description  |
|--------------|--------|----------|--------------|
| `site_url`   | string | Yes      | Website URL  |
| `license_key`| string | Yes      | License key  |

### Success Response (200)
```json
{
  "status": 200,
  "message": "Data found",
  "data": [
    {
      "app_name": "My App",
      "build_target": "android",
      "status": "Completed",
      "created_date": "12-Mar-2026",
      "created_time": "14:30:00 PM",
      "apk_name": "app_build_001.apk",
      "aab_name": "app_build_001.aab",
      "apk_url": "https://storage.example.com/app.apk",
      "aab_url": "https://storage.example.com/app.aab"
    },
    {
      "app_name": "My App",
      "build_target": "ios",
      "status": "Completed",
      "created_date": "12-Mar-2026",
      "created_time": "14:30:00 PM"
    }
  ]
}
```

> **Note:** `apk_url`, `aab_url`, `apk_name`, `aab_name` are only present for `android` builds. URLs are `null` if the build status is `delete`.

### Build Statuses
- `Pending`
- `Processing`
- `Completed`
- `Failed`
- `Delete`

### Error Responses

| Status | Message |
|--------|---------|
| 401    | Unauthorized |
| 404    | Site URL is required / License key is required / Domain not found / Build domain not found |

---

## 6. Push Notification Resource

Uploads push notification configuration files (Android JSON / iOS plist).

**Endpoint:** `POST /appza/v1/build/push-notification-resource`

### Request Body

| Field                          | Type         | Required                                    | Description                              |
|--------------------------------|--------------|---------------------------------------------|------------------------------------------|
| `site_url`                     | string       | Yes                                         | Valid URL                                |
| `license_key`                  | string       | Yes                                         | License key                              |
| `android_notification_content` | object/string| Required if `ios_notification_content` is empty | Firebase JSON config for Android       |
| `ios_notification_content`     | string       | Required if `android_notification_content` is empty | APNs plist content for iOS          |

> At least one of `android_notification_content` or `ios_notification_content` must be provided.

### Success Response (200)
```json
{
  "status": 200,
  "url": "...",
  "method": "POST",
  "message": "Successfully pushed notification information updated."
}
```

### Error Responses

| Status | Message |
|--------|---------|
| 404    | Domain or license key is incorrect |

---

## 7. Build Response (Builder Callback)

Called by the builder application to update build status. **This is an internal/server-to-server endpoint.**

**Endpoint:** `POST /appza/v1/build/response/{id}`

| Param           | Type   | Required | Description           |
|-----------------|--------|----------|-----------------------|
| `id` (URL)      | int    | Yes      | Build order ID        |
| `build_message` | string | No       | Build status message  |

---

## 8. Process Start (Builder Callback)

Called by the builder application when it starts processing. **This is an internal/server-to-server endpoint.**

**Endpoint:** `POST /appza/v1/build/process-start/{id}`

| Param           | Type   | Required | Description           |
|-----------------|--------|----------|-----------------------|
| `id` (URL)      | int    | Yes      | Build order ID        |
| `build_message` | string | No       | Status message        |

---

## Build Flow (Recommended Order)

```
1. POST /build/resource          → Setup app resources (logo, splash, platform)
2. POST /build/ios-keys-verify   → (iOS only) Upload & verify iOS keys
3. POST /build/ios-check-app-name→ (iOS only) Verify iOS app name
4. POST /build/push-notification-resource → (Optional) Upload push notification files
5. POST /build                   → Start the build
6. GET  /build/history           → Poll build status
```