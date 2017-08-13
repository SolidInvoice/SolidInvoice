@api
Feature: Delete an Quote
  In order to keep information clean
  As a user
  I need to be able to delete quotes

  Background:
    Given I have the following users:
      | username | password | roles            |
      | abc      | abc      | ROLE_SUPER_ADMIN |
    And I am authorised as "abc"

  @resetSchema
  @alice(Quote)
  Scenario: Delete an quote
    When I send a JSON "DELETE" request to "/api/quotes/1"
    Then the response status code should be 204
    And 1 quote should have been deleted