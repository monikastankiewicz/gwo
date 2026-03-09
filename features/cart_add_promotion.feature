Feature: Apply promotions to cart
  As a user
  I want to apply promotions to my cart
  So that I can receive discounts on my order

  Scenario: User applies an item promotion to the cart
    Given there exist following users:
      | id | name      |
      | 30 | Test User |
    And there exist following products:
      | id | code | name       | type | price | taxRate |
      | 30 | P030 | Product 30 | book | 1000  | 23      |
      | 31 | P031 | Product 31 | book | 1000  | 23      |
    And there exists a cart with id "50" for user "30" containing items:
      | productId | quantity |
      | 30        | 1        |
      | 31        | 1        |
    And there exist following promotions:
      | id | type  | percentageDiscount | productTypesFilter |
      | 1  | item  | 10                 | book               |
      | 2  | order | 20                 |                    |
    When user "30" adds promotion "1" to the cart "50"
    Then the response should be received with status code 200
    And the response should equal json:
    """
    {
      "message": "Promotion assigned to cart successfully."
    }
    """

  Scenario: User applies an order promotion to the cart
    Given there exist following users:
      | id | name      |
      | 30 | Test User |
    And there exist following products:
      | id | code | name       | type | price | taxRate |
      | 30 | P030 | Product 30 | book | 1000  | 23      |
      | 31 | P031 | Product 31 | book | 1000  | 23      |
    And there exists a cart with id "50" for user "30" containing items:
      | productId | quantity |
      | 30        | 1        |
      | 31        | 1        |
    And there exist following promotions:
      | id | type  | percentageDiscount | productTypesFilter |
      | 1  | item  | 10                 | book               |
      | 2  | order | 20                 |                    |

    When user "30" adds promotion "2" to the cart "50"
    Then the response should be received with status code 200
    And the response should equal json:
    """
    {
      "message": "Promotion assigned to cart successfully."
    }
    """

  Scenario: User cannot apply a second different order promotion to the cart
    Given there exist following users:
      | id | name      |
      | 30 | Test User |
    And there exist following products:
      | id | code | name       | type | price | taxRate |
      | 30 | P030 | Product 30 | book | 1000  | 23      |
      | 31 | P031 | Product 31 | book | 1000  | 23      |
    And there exists a cart with id "50" for user "30" containing items:
      | productId | quantity |
      | 30        | 1        |
      | 31        | 1        |
    And there exist following promotions:
      | id | type  | percentageDiscount | productTypesFilter |
      | 1  | item  | 10                 | book               |
      | 2  | order | 20                 |                    |
      | 3  | order | 10                 |                    |
    When user "30" adds promotion "2" to the cart "50"
    Then the response should be received with status code 200
    And the response should equal json:
    """
    {
      "message": "Promotion assigned to cart successfully."
    }
    """
    When user "30" adds promotion "3" to the cart "50"
    Then the response should be received with status code 422