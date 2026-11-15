---
mode: testing
url: http://localhost:4920/
max_steps: 50
---

# Install SolidInvoice

## Database
Click "Begin Installation".
Click the "Embedded Database (SQLite)" text to select that option, then click "Next".
Verify the "User account" step is shown.

## Administrator account
Select "English (United States)" as the Locale.
Type "Alex" as the First name, "Morgan" as the Last name, "alex@example.com" as the Email address and "Solid-Test-2026!" as the Password.
Uncheck the usage statistics checkbox and click "Next".
Verify the Review page shows "SQLITE" as the Driver, "Alex Morgan" as the Name and usage statistics as "Disabled".

## Install
Click "Install".
Wait for the progress to reach 100%, then click "Next".
Verify "Installation Complete!" is shown.
Click "Launch SolidInvoice".
Verify "Welcome to SolidInvoice!" is shown.
