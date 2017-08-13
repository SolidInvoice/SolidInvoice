@api
Feature: Delete an Invoice
  In order to keep information clean
  As a user
  I need to be able to delete invoices

  Background:
    Given I have the following users:
      | username | password | roles            |
      | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Invoice)
  Scenario: Delete an invoice
    When I send a JSON "DELETE" request to "/api/invoices/1"
    Then the response status code should be 204
    And 1 invoice should have been deleted