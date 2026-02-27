@local @local_snapmultilangnames
Feature: Basic tests for Snap multilang names

  @javascript
  Scenario: Plugin local_snapmultilangnames appears in the list of installed additional plugins
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Additional plugins"
    Then I should see "Snap multilang names"
    And I should see "local_snapmultilangnames"
