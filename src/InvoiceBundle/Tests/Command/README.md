# SendInvoiceRemindersCommand Testing

The `SendInvoiceRemindersCommand` is tested primarily through functional tests rather than unit tests.

## Why No Unit Tests?

The command extends `SolidWorx\Platform\PlatformBundle\Console\Command` which requires specific IO object setup that makes unit testing complex. The command also interacts with:
- Company filter management
- Multiple repository queries
- Message bus dispatching
- Clock-based time logic

This level of integration is better suited for functional testing.

## Where is it Tested?

- **Functional Tests**: `InvoiceReminderFlowTest` - Tests the complete reminder flow triggered by the command
- **Integration**: The command's behavior is verified through end-to-end scenarios

## What Would Unit Tests Cover?

If unit testing were practical, they would cover:
- Command disables and re-enables company filter
- Command dispatches pre-due reminder messages
- Command dispatches overdue reminder messages
- Command dispatches both pre-due and overdue messages
- Command re-enables company filter even on error

All of these behaviors are verified in the functional test suite where the command runs in a real application context.
