# CompanySiteLibrary APIs

This collection provides a RESTful-style API for managing company site library data including employees, attendance, payroll, departments, helpdesk tickets, and employee bank records.

All requests are routed through a single base URL stored in the `{{base_url}}` collection variable, using query parameters to specify the action and target table.

---

## Base URL

```
{{base_url}}
```

---

## Authentication

Most state-changing endpoints (POST, PUT, DELETE) require a valid CSRF token passed via the `X-CSRF-Token` header. The collection pre-request script automatically fetches and caches this token. Auth endpoints (login, register) are exempt.

Session-based authentication is used. Call **Login** first to establish a session before accessing protected resources.

---

## Variables

| Variable | Description |
|---|---|
| `{{base_url}}` | The base URL of the API server |
| `{{csrf_token}}` | Auto-fetched CSRF token (managed by pre-request script) |

---

## Endpoints

### Auth

#### Get CSRF Token
- **Method:** `GET`
- **URL:** `{{base_url}}?action=csrf`
- **Description:** Retrieves a CSRF token required for state-changing requests. The test script automatically saves the token to `{{csrf_token}}`.
- **Headers:** None required
- **Response:** `{ "csrf_token": "<token>" }`

---

#### Register
- **Method:** `POST`
- **URL:** `{{base_url}}?action=register`
- **Description:** Creates a new user account. Does not require a CSRF token.
- **Headers:**
  - `Content-Type: application/json`
- **Request Body:**
```json
{
  "username": "nana",
  "email": "nana@acs.io",
  "password": "nana1234",
  "full_name": "Nana Agyeman"
}
```
- **Response:** `{ "success": true, "message": "...", "user": {...} }`

---

#### Login
- **Method:** `POST`
- **URL:** `{{base_url}}?action=login`
- **Description:** Authenticates a user and establishes a session. Does not require a CSRF token.
- **Headers:**
  - `Content-Type: application/json`
- **Request Body:**
```json
{
  "identifier": "nana",
  "password": "nana1234"
}
```
- **Response:** `{ "success": true, "message": "...", "user": {...}, "token": "..." }`

---

#### Me (Current User)
- **Method:** `GET`
- **URL:** `{{base_url}}?action=me`
- **Description:** Returns the currently authenticated user's profile.
- **Headers:** None required

---

#### Logout
- **Method:** `POST`
- **URL:** `{{base_url}}?action=logout`
- **Description:** Ends the current user session.
- **Headers:**
  - `X-CSRF-Token: {{csrf_token}}`

---

### Employees CRUD

#### List Employees
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=employees`
- **Description:** Returns a list of all employees.

---

#### Get Employee
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=employees&id={id}`
- **Description:** Returns a single employee by ID.
- **Query Params:**
  - `id` — The employee's ID

---

#### Create Employee
- **Method:** `POST`
- **URL:** `{{base_url}}?action=crud&table=employees`
- **Headers:**
  - `Content-Type: application/json`
  - `X-CSRF-Token: {{csrf_token}}`
- **Request Body:**
```json
{
  "name": "Reginald Gwira",
  "email": "reginald.gwira@company.org",
  "department": "Engineering",
  "position": "Developer Intern",
  "phone": "0244110114"
}
```
- **Response:** `{ "success": true, "message": "...", "id": ... }`

---

#### Update Employee
- **Method:** `PUT`
- **URL:** `{{base_url}}?action=crud&table=employees&id={id}`
- **Headers:**
  - `Content-Type: application/json`
  - `X-CSRF-Token: {{csrf_token}}`
- **Query Params:**
  - `id` — The employee's ID
- **Request Body:**
```json
{
  "name": "John Doe",
  "email": "john.doe@company.com",
  "department": "Engineering",
  "position": "Senior Developer"
}
```
- **Response:** `{ "success": true, "message": "...", "data": {...} }`

---

#### Delete Employee
- **Method:** `DELETE`
- **URL:** `{{base_url}}?action=crud&table=employees&id={id}`
- **Headers:**
  - `X-CSRF-Token: {{csrf_token}}`
- **Query Params:**
  - `id` — The employee's ID

---

### Attendance

#### List Attendance
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=attendance`
- **Description:** Returns all attendance records.

---

#### Get Attendance
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=attendance&id={id}`
- **Query Params:**
  - `id` — The attendance record ID

---

#### Create Attendance
- **Method:** `POST`
- **URL:** `{{base_url}}?action=crud&table=attendance`
- **Headers:**
  - `Content-Type: application/json`
  - `X-CSRF-Token: {{csrf_token}}`
- **Request Body:**
```json
{
  "empid": "8",
  "deptid": "1",
  "workstart": "2026-07-16 8:24:59",
  "workend": "2026-07-16 5:25:00"
}
```

---

#### Update Attendance
- **Method:** `PUT`
- **URL:** `{{base_url}}?action=crud&table=attendance&id={id}`
- **Query Params:**
  - `id` — The attendance record ID
- **Request Body:** Same fields as Create Attendance (include only fields to update)

---

#### Delete Attendance
- **Method:** `DELETE`
- **URL:** `{{base_url}}?action=crud&table=attendance&id={id}`
- **Headers:**
  - `X-CSRF-Token: {{csrf_token}}`
- **Query Params:**
  - `id` — The attendance record ID

---

### Employee Bank

#### List Employee Bank
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=employeebank`
- **Description:** Returns all employee bank records.

---

#### Get Employee Bank
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=employeebank&id={id}`
- **Query Params:**
  - `id` — The bank record ID

---

#### Create Employee Bank
- **Method:** `POST`
- **URL:** `{{base_url}}?action=crud&table=employeebank`
- **Headers:**
  - `Content-Type: application/json`
  - `X-CSRF-Token: {{csrf_token}}`
- **Request Body:**
```json
{
  "name": "Ecobank Ghana"
}
```

---

#### Update Employee Bank
- **Method:** `PUT`
- **URL:** `{{base_url}}?action=crud&table=employeebank&id={id}`
- **Headers:**
  - `X-CSRF-Token: {{csrf_token}}`
- **Query Params:**
  - `id` — The bank record ID
- **Request Body:** Same fields as Create Employee Bank (include only fields to update)

---

#### Delete Employee Bank
- **Method:** `DELETE`
- **URL:** `{{base_url}}?action=crud&table=employeebank&id={id}`
- **Headers:**
  - `X-CSRF-Token: {{csrf_token}}`
- **Query Params:**
  - `id` — The bank record ID

---

### Helpdesk

#### List Helpdesk
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=helpdesk`
- **Description:** Returns all helpdesk tickets.

---

#### Get Helpdesk
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=helpdesk&id={id}`
- **Query Params:**
  - `id` — The helpdesk ticket ID

---

#### Create Helpdesk
- **Method:** `POST`
- **URL:** `{{base_url}}?action=crud&table=helpdesk`
- **Headers:**
  - `Content-Type: application/json`
- **Request Body:**
```json
{
  "name": "",
  "complaint": "",
  "assignedto": "",
  "status": "",
  "feedback": "",
  "entrydate": ""
}
```

---

#### Update Helpdesk
- **Method:** `PUT`
- **URL:** `{{base_url}}?action=crud&table=helpdesk&id={id}`
- **Query Params:**
  - `id` — The helpdesk ticket ID
- **Request Body:** Same fields as Create Helpdesk (include only fields to update)

---

#### Delete Helpdesk
- **Method:** `DELETE`
- **URL:** `{{base_url}}?action=crud&table=helpdesk&id={id}`
- **Headers:**
  - `X-CSRF-Token: {{csrf_token}}`
- **Query Params:**
  - `id` — The helpdesk ticket ID

---

### Payroll

#### List Payroll
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=payroll`
- **Description:** Returns all payroll records.

---

#### Get Payroll
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=payroll&id={id}`
- **Query Params:**
  - `id` — The payroll record ID

---

#### Create Payroll
- **Method:** `POST`
- **URL:** `{{base_url}}?action=crud&table=payroll`
- **Headers:**
  - `Content-Type: application/json`
  - `X-CSRF-Token: {{csrf_token}}`
- **Request Body:**
```json
{
  "month": "July",
  "employee": "8",
  "grosssalary": "6500",
  "deductions": "1300",
  "netsalary": "4200",
  "bank": "2",
  "accountno": "32942059209555",
  "ssnitid": "140157025555"
}
```

---

#### Update Payroll
- **Method:** `PUT`
- **URL:** `{{base_url}}?action=crud&table=payroll&id={id}`
- **Headers:**
  - `X-CSRF-Token: {{csrf_token}}`
- **Query Params:**
  - `id` — The payroll record ID
- **Request Body:** Same fields as Create Payroll (include only fields to update)

---

#### Delete Payroll
- **Method:** `DELETE`
- **URL:** `{{base_url}}?action=crud&table=payroll&id={id}`
- **Headers:**
  - `X-CSRF-Token: {{csrf_token}}`
- **Query Params:**
  - `id` — The payroll record ID

---

### Department

#### List Departments
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=department`
- **Description:** Returns all departments.

---

#### Get Department
- **Method:** `GET`
- **URL:** `{{base_url}}?action=crud&table=department&id={id}`
- **Query Params:**
  - `id` — The department ID

---

## CSRF Token Handling

The collection pre-request script automatically fetches a CSRF token before any POST, PUT, or DELETE request (except login and register). The token is cached in `{{csrf_token}}` and reused until cleared.

To manually fetch a token, call the **Get CSRF Token** endpoint and the test script will save it automatically.

---

## Collection-Level Tests

Every request in this collection runs the following checks automatically:

- Response time is under 3000ms
- Response body is not empty
- Content-Type header includes `application/json`