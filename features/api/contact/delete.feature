@api @contact
Feature: Delete Contact
  In order to keep information clean
  As a user
  I need to be able to delete contacts

  Background:
    Given I have the following users:
      | username | password | roles            |
      | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Client)
  Scenario: Delete a contact
    When I send a JSON "DELETE" request to "/api/contacts/2"
    Then the response status code should be 204
    And should be 0 contacts like:
    | firstName | Two         |
    | email     | two@two.com |