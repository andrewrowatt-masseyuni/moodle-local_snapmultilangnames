@local @local_snapmultilangnames
Feature: Section language settings are persisted after save

  Background:
    Given the following config values are set as admin:
      | enablemultilangnames | 1 | local_snapmultilangnames |
    And the following "courses" exist:
      | fullname     | shortname | format |
      | Test Course  | C1        | topics |
    And the multilang names feature is enabled for course "C1"

  @javascript
  Scenario: Language setting for a section component is saved and displayed on reload
    Given I log in as "admin"
    And I am on the multilang settings page for course "C1"
    When I click on "English (en)" "button" in the "[data-section-num='1'] .mlnc-form-preview > div:first-child" "css_element"
    And I click on "Māori (mi)" "button" in the ".dropdown-menu.show" "css_element"
    And I press "Save changes"
    Then I should see "Language settings saved"
    When I am on the multilang settings page for course "C1"
    Then section 1 component 1 dropdown should show "Māori (mi)"
