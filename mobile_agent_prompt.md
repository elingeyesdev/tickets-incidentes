# Mobile Agent Prompt: Ticket System Updates

## Context
The backend and web frontend for the Ticket Management system have been updated. You need to reflect these changes in the Mobile App. The key updates involve adding a **Priority** field and a **Conditional Area** field to the Ticket Creation flow and Ticket Details view.

## 1. Ticket Creation Flow

### A. New Field: Priority
You must add a priority selector to the Create Ticket form.

*   **Type:** Required.
*   **Values:** `low`, `medium`, `high`.
*   **Default:** `medium` (or force user selection).
*   **UI Suggestion:** Use a segmented control or a row of selectable chips.
    *   **Low:** Green color coding.
    *   **Medium:** Yellow/Amber color coding.
    *   **High:** Red color coding.

### B. New Field: Area (Conditional)
This is a dynamic field that **only appears** if the selected company has enabled the "Areas" feature.

**Workflow:**
1.  **User Selects Company:** When the user selects a company in the form.
2.  **Check Feature Flag:** Call the following endpoint to check if the company uses areas:
    *   **Endpoint:** `GET /api/companies/{companyId}/settings/areas-enabled`
    *   **Response:** `{ "data": { "areas_enabled": true } }` (or `false`)
3.  **Conditional UI:**
    *   **If `areas_enabled` is `false`:** Do NOT show the Area selector. Send `area_id: null` (or omit it) in the create payload.
    *   **If `areas_enabled` is `true`:** Show the Area selector (Dropdown/Picker).
        *   **Fetch Areas:** Call `GET /api/areas?company_id={companyId}&is_active=true`
        *   **Selection:** This field is **Optional** for the user (they can leave it empty unless your specific business logic says otherwise, but the backend treats it as nullable).

## 2. Ticket Details View

Update the Ticket Details screen to display the new information.

*   **Priority:** Show a badge/icon indicating the priority (Low/Medium/High) with appropriate colors.
*   **Area:** Display the Area name if `area_id` is not null.

## 3. API Response Structure (Reference)

Be aware that the API response for Ticket Details (`GET /api/tickets/{id}`) and List (`GET /api/tickets`) returns both the IDs and the full objects.

**Current Response Format (What you will receive):**
```json
{
  "data": {
    "id": "...",
    "ticket_code": "TKT-2025-00015",
    "title": "Example Ticket",
    "priority": "high",       // <--- NEW FIELD
    "status": "open",
    
    // Redundant IDs (Root level)
    "company_id": "...",
    "category_id": "...",
    "area_id": "...",         // <--- NEW FIELD (Nullable)
    
    // Full Objects (Nested)
    "company": { "id": "...", "name": "..." },
    "category": { "id": "...", "name": "..." },
    "area": {                 // <--- NEW OBJECT (Nullable)
      "id": "...",
      "name": "Logistics"
    }
  }
}
```

## Summary of Tasks
1.  **Create Ticket Screen:**
    *   Add Priority Selector.
    *   Implement `checkCompanyAreas` logic.
    *   Add Area Selector (hidden by default, shown if enabled).
2.  **Ticket Details Screen:**
    *   Display Priority.
    *   Display Area (if exists).
