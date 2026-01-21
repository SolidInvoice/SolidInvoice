# SendInvoiceReminderHandler Testing

The `SendInvoiceReminderHandler` is tested primarily through functional tests rather than unit tests.

## Why No Unit Tests?

The handler depends on `CompanySelector`, which is a `final` class with complex internal dependencies (`EntityManager`, `Connection`, `FilterCollection`, etc.). This makes it impractical to mock for unit testing without excessive setup.

## Where is it Tested?

- **Functional Tests**: `InvoiceReminderFlowTest` - Tests the complete reminder flow including company context management
- **Integration**: The handler's behavior is verified through end-to-end reminder scenarios

## What Would Unit Tests Cover?

If unit testing were practical, they would cover:
- Handler sets and resets company context
- Handler resets context even on errors  
- Handler skips when reminders disabled
- Handler skips when invoice not found
- Handler sends emails to clients
- Handler sends internal notifications
- Handler creates reminder records
- Handler sends escalation notifications for final reminders

All of these behaviors are verified in the functional test suite.
