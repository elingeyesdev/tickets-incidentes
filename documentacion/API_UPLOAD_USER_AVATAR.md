# API Endpoint: Upload User Avatar

## Overview
Endpoint para subir la foto de perfil del usuario autenticado. Solo el usuario propietario del perfil puede subir su propia foto.

---

## Endpoint Details

### URL
```
POST /api/users/me/avatar
```

### Base URL
```
http://localhost:8000/api  (Development)
https://api.helpdesk.com/api  (Production)
```

### Full URL Example
```
https://api.helpdesk.com/api/users/me/avatar
```

---

## Authentication

**Required:** YES - JWT Bearer Token

### Header
```
Authorization: Bearer {access_token}
```

### Token Specifications
- **Type:** JWT (JSON Web Token)
- **Access Token Validity:** 15 minutes
- **Refresh Token Validity:** 7 days
- **Stateless:** No database lookup required per request

### Example Header
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_XgL0n3I9PlFUP0THsR8U
```

---

## Request Format

### Content-Type
```
multipart/form-data
```

### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| avatar | File | Yes | Image file (JPEG, PNG, GIF, WebP) |

### File Specifications

| Property | Constraint |
|----------|-----------|
| **Supported Formats** | JPEG, JPG, PNG, GIF, WebP |
| **Max Size** | 5 MB |
| **Min Size** | 1 byte |
| **MIME Types Accepted** | `image/jpeg`, `image/png`, `image/gif`, `image/webp` |

### Request Body Examples

#### Using cURL
```bash
curl -X POST "https://api.helpdesk.com/api/users/me/avatar" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -F "avatar=@/path/to/avatar.jpg"
```

#### Using JavaScript (Fetch API)
```javascript
const formData = new FormData();
formData.append('avatar', fileInput.files[0]);

const response = await fetch('https://api.helpdesk.com/api/users/me/avatar', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${accessToken}`
  },
  body: formData
});

const data = await response.json();
```

#### Using JavaScript (Axios)
```javascript
const formData = new FormData();
formData.append('avatar', fileInput.files[0]);

const response = await axios.post(
  'https://api.helpdesk.com/api/users/me/avatar',
  formData,
  {
    headers: {
      'Authorization': `Bearer ${accessToken}`,
      'Content-Type': 'multipart/form-data'
    }
  }
);
```

#### Using Kotlin (Android)
```kotlin
val file = File("/path/to/avatar.jpg")
val requestFile = file.asRequestBody("image/jpeg".toMediaType())
val multipartBody = MultipartBody.Part.createFormData("avatar", file.name, requestFile)

val apiService = Retrofit
    .Builder()
    .baseUrl("https://api.helpdesk.com/api/")
    .build()
    .create(ApiService::class.java)

val response = apiService.uploadAvatar(
    authorization = "Bearer $accessToken",
    avatar = multipartBody
)

// Interface definition:
interface ApiService {
    @Multipart
    @POST("users/me/avatar")
    suspend fun uploadAvatar(
        @Header("Authorization") authorization: String,
        @Part avatar: MultipartBody.Part
    ): AvatarResponse
}
```

#### Using Swift (iOS)
```swift
var request = URLRequest(url: URL(string: "https://api.helpdesk.com/api/users/me/avatar")!)
request.httpMethod = "POST"
request.setValue("Bearer \(accessToken)", forHTTPHeaderField: "Authorization")

let boundary = UUID().uuidString
request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")

var body = Data()
let imageData = image.jpegData(compressionQuality: 0.8)!

// Add image to multipart body
body.append("--\(boundary)\r\n".data(using: .utf8)!)
body.append("Content-Disposition: form-data; name=\"avatar\"; filename=\"avatar.jpg\"\r\n".data(using: .utf8)!)
body.append("Content-Type: image/jpeg\r\n\r\n".data(using: .utf8)!)
body.append(imageData)
body.append("\r\n--\(boundary)--\r\n".data(using: .utf8)!)

request.httpBody = body

URLSession.shared.dataTask(with: request) { data, response, error in
    if let data = data {
        let decoder = JSONDecoder()
        let response = try! decoder.decode(AvatarResponse.self, from: data)
        print(response)
    }
}.resume()
```

---

## Response Format

### Success Response (200 OK)
```json
{
  "message": "Avatar uploaded successfully",
  "data": {
    "avatarUrl": "http://localhost:8000/storage/avatars/550e8400-e29b-41d4-a716-446655440000/1731774123_profile-photo.jpg"
  }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| message | String | Success message |
| data | Object | Response data container |
| data.avatarUrl | String | Full URL to uploaded avatar image |

---

## HTTP Status Codes

| Status Code | Meaning | When It Occurs |
|-------------|---------|----------------|
| **200** | OK | Avatar uploaded successfully |
| **400** | Bad Request | Invalid request format |
| **401** | Unauthorized | Missing or invalid JWT token |
| **422** | Unprocessable Entity | Validation error (file type, size, etc.) |
| **429** | Too Many Requests | Rate limit exceeded (3 uploads per hour) |
| **500** | Internal Server Error | Server error |

---

## Error Responses

### 401 - Unauthorized (Missing Token)
```json
{
  "message": "Unauthenticated."
}
```

### 401 - Unauthorized (Invalid Token)
```json
{
  "message": "Unauthenticated."
}
```

### 422 - Validation Error (No File)
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "avatar": [
      "Avatar image is required"
    ]
  }
}
```

### 422 - Validation Error (Invalid File Type)
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "avatar": [
      "Avatar must be a valid image (JPEG, PNG, GIF, WebP)"
    ]
  }
}
```

### 422 - Validation Error (File Too Large)
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "avatar": [
      "Avatar must not exceed 5 MB"
    ]
  }
}
```

### 429 - Rate Limited
```json
{
  "message": "Too Many Requests"
}
```

---

## Rate Limiting

### Limits
- **3 uploads per hour** per user
- **Per-user basis:** Each user has independent limit

### Rate Limit Headers
The response includes rate limit information in headers:

```
X-RateLimit-Limit: 3
X-RateLimit-Remaining: 2
X-RateLimit-Reset: 1731778323
```

### Handling Rate Limiting
When you receive a **429 status code**, wait until the `X-RateLimit-Reset` timestamp before retrying.

---

## File Storage Details

### Storage Disk
- **Type:** Public (accessible via URL)
- **Location:** `/storage/app/public/avatars`

### File Path Structure
```
storage/app/public/avatars/{user_id}/{timestamp}_{slug_filename}.{extension}
```

### Example Path
```
storage/app/public/avatars/550e8400-e29b-41d4-a716-446655440000/1731774123_profile-photo.jpg
```

### Accessing the Image
```
https://api.helpdesk.com/storage/avatars/{user_id}/{filename}
```

### Image URL Format
The API returns a full asset URL:
```
http://localhost:8000/storage/avatars/550e8400-e29b-41d4-a716-446655440000/1731774123_profile-photo.jpg
```

### URL Lifespan
- **Permanent:** URLs are permanent as long as the file exists
- **CDN:** Can be cached and served via CDN
- **Direct Access:** Can be accessed directly without authentication

---

## Validation Rules

### File Requirements
```
✓ Must be a file (not null or empty)
✓ Max size: 5 MB (5120 KB)
✓ Allowed MIME types:
  - image/jpeg
  - image/png
  - image/gif
  - image/webp
```

### Filename Handling
- Original filename is slugified (spaces to hyphens, special chars removed)
- Current timestamp added for uniqueness
- Old avatars are NOT automatically deleted

### Example
```
Original filename: "My Profile Photo.jpg"
Stored as:         "1731774123_my-profile-photo.jpg"
```

---

## Complete Implementation Examples

### React Native Example
```javascript
import * as ImagePicker from 'expo-image-picker';
import axios from 'axios';

const uploadAvatar = async (accessToken) => {
  // Pick image from device
  const result = await ImagePicker.launchImageLibraryAsync({
    mediaTypes: ImagePicker.MediaTypeOptions.Images,
    allowsEditing: true,
    aspect: [1, 1],
    quality: 0.8,
  });

  if (result.cancelled) return;

  // Prepare form data
  const formData = new FormData();
  formData.append('avatar', {
    uri: result.uri,
    type: 'image/jpeg',
    name: 'avatar.jpg',
  });

  try {
    // Upload
    const response = await axios.post(
      'https://api.helpdesk.com/api/users/me/avatar',
      formData,
      {
        headers: {
          'Authorization': `Bearer ${accessToken}`,
          'Content-Type': 'multipart/form-data',
        },
      }
    );

    console.log('Avatar uploaded:', response.data.data.avatarUrl);
    return response.data.data.avatarUrl;
  } catch (error) {
    if (error.response?.status === 429) {
      console.error('Rate limited. Try again later.');
    } else if (error.response?.status === 422) {
      console.error('Validation error:', error.response.data.errors);
    } else {
      console.error('Upload failed:', error.message);
    }
  }
};
```

### Flutter Example
```dart
import 'package:dio/dio.dart';
import 'package:image_picker/image_picker.dart';

Future<String?> uploadAvatar(String accessToken) async {
  final picker = ImagePicker();
  final pickedFile = await picker.pickImage(source: ImageSource.gallery);

  if (pickedFile == null) return null;

  try {
    final formData = FormData.fromMap({
      'avatar': await MultipartFile.fromFile(
        pickedFile.path,
        filename: 'avatar.jpg',
        contentType: DioMediaType.parse('image/jpeg'),
      ),
    });

    final dio = Dio();
    final response = await dio.post(
      'https://api.helpdesk.com/api/users/me/avatar',
      data: formData,
      options: Options(
        headers: {
          'Authorization': 'Bearer $accessToken',
        },
      ),
    );

    if (response.statusCode == 200) {
      return response.data['data']['avatarUrl'];
    }
  } on DioError catch (e) {
    if (e.response?.statusCode == 429) {
      print('Rate limited. Try again later.');
    } else if (e.response?.statusCode == 422) {
      print('Validation error: ${e.response?.data['errors']}');
    }
  }

  return null;
}
```

---

## Best Practices

### 1. **Image Optimization**
```javascript
// Compress before upload to reduce bandwidth
const canvas = document.createElement('canvas');
canvas.width = 400;
canvas.height = 400;
const ctx = canvas.getContext('2d');
ctx.drawImage(image, 0, 0, 400, 400);
const compressedBlob = await canvas.convertToBlob({ quality: 0.8 });
```

### 2. **Handle Rate Limiting**
```javascript
const uploadWithRetry = async (file, accessToken, maxRetries = 3) => {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await uploadAvatar(file, accessToken);
    } catch (error) {
      if (error.response?.status === 429) {
        const retryAfter = parseInt(error.response.headers['x-ratelimit-reset']) * 1000;
        const waitTime = retryAfter - Date.now();
        console.log(`Rate limited. Waiting ${waitTime}ms...`);
        await new Promise(resolve => setTimeout(resolve, waitTime));
      } else {
        throw error;
      }
    }
  }
};
```

### 3. **Progress Tracking**
```javascript
const uploadWithProgress = async (file, accessToken, onProgress) => {
  const formData = new FormData();
  formData.append('avatar', file);

  return axios.post(
    'https://api.helpdesk.com/api/users/me/avatar',
    formData,
    {
      headers: {
        'Authorization': `Bearer ${accessToken}`,
      },
      onUploadProgress: (progressEvent) => {
        const percentComplete = (progressEvent.loaded / progressEvent.total) * 100;
        onProgress(percentComplete);
      },
    }
  );
};
```

### 4. **Token Refresh**
```javascript
const uploadWithTokenRefresh = async (file, accessToken, refreshToken) => {
  try {
    return await uploadAvatar(file, accessToken);
  } catch (error) {
    if (error.response?.status === 401) {
      // Token expired, refresh it
      const newAccessToken = await refreshAccessToken(refreshToken);
      return uploadAvatar(file, newAccessToken);
    }
    throw error;
  }
};
```

### 5. **Display Avatar**
```javascript
// Use the returned URL immediately to show avatar
const response = await uploadAvatar(file, accessToken);
const avatarUrl = response.data.data.avatarUrl;

// Display in UI
document.getElementById('avatar-image').src = avatarUrl;

// Or in React
<img src={avatarUrl} alt="User Avatar" className="avatar" />
```

---

## Testing

### Using Postman
```
1. Create new POST request
2. URL: https://api.helpdesk.com/api/users/me/avatar
3. Headers:
   - Authorization: Bearer YOUR_TOKEN
4. Body:
   - Type: form-data
   - Key: avatar
   - Value: Select file from computer
5. Click Send
```

### Using Thunder Client (VS Code)
```
Method: POST
URL: https://api.helpdesk.com/api/users/me/avatar
Headers:
  Authorization: Bearer YOUR_TOKEN
Files:
  avatar: /path/to/image.jpg
```

---

## Security Considerations

### 1. **Authentication**
- JWT token must be included in every request
- Tokens expire after 15 minutes
- User can only upload their own avatar

### 2. **File Validation**
- File type validated by MIME type
- File size limited to 5 MB
- No executable files allowed

### 3. **Rate Limiting**
- 3 uploads per hour per user
- Prevents abuse and reduces server load

### 4. **CORS**
- Public endpoint (accessible from mobile apps)
- CORS headers configured for mobile clients

---

## Troubleshooting

### "Unauthenticated" Error
**Solution:** Check that:
- Token is included in Authorization header
- Token has not expired (refresh if needed)
- Token format is correct: `Bearer {token}`

### "Avatar image is required"
**Solution:**
- Ensure file is included in request body
- Parameter name must be exactly: `avatar`
- File must not be empty

### "Avatar must be a valid image"
**Solution:**
- Check file format (only JPEG, PNG, GIF, WebP allowed)
- Check MIME type header
- Try converting image to PNG or JPEG

### "Avatar must not exceed 5 MB"
**Solution:**
- Compress image before upload
- Reduce image dimensions
- Use lower quality JPEG

### "Too Many Requests" (429)
**Solution:**
- Wait 1 hour before uploading again
- Check `X-RateLimit-Reset` header
- Implement exponential backoff retry logic

---

## Support

For issues or questions:
- Email: support@helpdesk.com
- API Status: https://status.helpdesk.com
- Documentation: https://docs.helpdesk.com/api

---

## Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-11-26 | Initial release |

---

## Related Endpoints

- `GET /api/users/me` - Get current user profile
- `PATCH /api/users/me` - Update user profile
- `POST /api/companies/{company}/logo` - Upload company logo
- `POST /api/companies/{company}/favicon` - Upload company favicon
