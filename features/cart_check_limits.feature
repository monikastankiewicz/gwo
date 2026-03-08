Feature: Cart limits
  As a system
  I want to limit products in the cart
  So that the cart stays valid

  Scenario: User cannot add more than the maximum quantity of a product already in the cart
    Given there exist following users:
      | id | name      |
      | 3  | Test User |
    And there exist following products:
      | id | code | name      | type | price | taxRate |
      | 3  | P003 | Product 3 | book | 1000  | 23      |
    And there exists a cart for user "3" with items:
      | productId | quantity |
      | 3         | 10       |
    When user "3" adds product "3" to the cart
    Then the response should be received with status code 422
    And the product "3" in user "3" cart should still have quantity 10

  Scenario: User cannot add more than the maximum number of distinct products to the cart
    Given there exist following users:
      | id | name      |
      | 4  | Test User |
    And there exist following products:
      | id | code | name       | type | price | taxRate |
      | 10 | P004 | Product 10 | book | 1000  | 23      |
      | 11 | P005 | Product 11 | book | 2000  | 23      |
      | 12 | P006 | Product 12 | book | 3000  | 23      |
      | 13 | P007 | Product 13 | book | 4000  | 23      |
      | 14 | P008 | Product 14 | book | 5000  | 23      |
      | 15 | P009 | Product 15 | book | 6000  | 23      |
    And there exists a cart for user "4" with items:
      | productId | quantity |
      | 10        | 1        |
      | 11        | 1        |
      | 12        | 1        |
      | 13        | 1        |
      | 14        | 1        |
    When user "4" adds product "15" to the cart
    Then the response should be received with status code 422
    And the user "4" cart should still contain 5 distinct products