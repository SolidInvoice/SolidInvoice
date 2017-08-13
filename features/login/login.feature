@login @javascript
Feature: Log into application
  In order to use the application
  As a user
  I need to be able to log into the application

  Scenario: Redirect to login page when going to the home page
    Given I am on the homepage
    Then I should be on "/login"

  Scenario: Login with invalid credentials
    Given I am on "/login"
    And I fill in "Username" with "fakeuser"
    And I fill in "Password" with "fakepass"
    And I press "login_button"
    Then I should see "Bad credentials" in the ".callout-danger" element

  @resetSchema
  Scenario: Login with valid credentials
    Given I have the following users:
      | username      | password   | roles            |
      | testuser      | testpass   | ROLE_SUPER_ADMIN |
    And I am on "/login"
    And I fill in "Username" with "testuser"
    And I fill in "Password" with "testpass"
    And I press "login_button"
    Then I should be on "/dashboard"
