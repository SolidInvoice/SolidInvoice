---
mode: testing
url: http://localhost:4920/login
max_steps: 50
---

# Create a first invoice

## Sign in
Type "alex@example.com" as the Email address and "Solid-Test-2026!" as the Password, then click "Sign in".
Verify "Welcome to SolidInvoice!" is shown.

## Business and client
Type "Morgan Design Studio" as the Company Name and click "Continue".
Type "Acme Corp" as the client name and "billing@acme.example" as the client email, then click "Continue".
Verify the first invoice step is shown.

## First invoice
Type "Website redesign" as the description and "1500" as the Amount, then click "Create & View My Invoice".
Verify "Invoice #1" is shown with status "Draft", a "Website redesign" line and a total of "$1,500.00".

## Client list
Click "Clients" in the top menu.
Verify "Acme Corp" is listed.
