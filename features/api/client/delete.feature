@api @client
Feature: Delete Client
  In order to keep information clean
  As a user
  I need to be able to delete clients

  Background:
    Given I have the following users:
      | username | password | roles            |
      | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Client)
  Scenario: Delete a client
    When I send a JSON "DELETE" request to "/api/clients/1"
    Then the response status code should be 204
    And 1 client should have been deleted