Feature: Calculate cart totals with promotions
  As a user
  I want cart totals to include promotions
  So that I can see the final discounted price

  Scenario: Cart totals include item promotion (10%) and order promotion (20%)
    Given there exist following users:
      | id | name      |
      | 10 | Test User |
    And there exist following products:
      | id | code | name      | type  | price | taxRate |
      | 20 | P001 | Product 1 | book  | 1000  | 23      |
      | 21 | P002 | Product 2 | audio | 500   | 23      |
    And there exists a cart with id "4" for user "10" containing items:
      | id | productId | quantity |
      | 1  | 20        | 1        |
      | 2  | 21        | 1        |
    And there exist following promotions:
      | id | type  | percentageDiscount | productTypesFilter |
      | 1  | item  | 10                 | book               |
      | 2  | order | 20                 |                    |
    And cart "4" has following promotions assigned:
      | promotionId |
      | 1           |
      | 2           |
    When user opens preview of cart "4"
    Then the response should be received with status code 200
    And the response should equal json:
     """
      {
        "itemsTotal": 1400,
        "adjustmentsTotal": -280,
        "taxTotal": 257,
        "total": 1120,
        "items": [
          {
            "product": {
              "code": "P001",
              "name": "Product 1"
            },
            "unitPrice": 1000,
            "discount": 10,
            "discountValue": 100,
            "distributedOrderDiscountValue": 180,
            "discountedUnitPrice": 720,
            "quantity": 1,
            "total": 720,
            "taxValue": 165
          },
          {
            "product": {
              "code": "P002",
              "name": "Product 2"
            },
            "unitPrice": 500,
            "discount": null,
            "discountValue": 0,
            "distributedOrderDiscountValue": 100,
            "discountedUnitPrice": 400,
            "quantity": 1,
            "total": 400,
            "taxValue": 92
          }
        ]
      }
     """

  Scenario: Cart totals include two item promotions (10% and 20%) applied sequentially
    Given there exist following users:
      | id | name      |
      | 10 | Test User |
    And there exist following products:
      | id | code | name      | type | price | taxRate |
      | 20 | P001 | Product 1 | book | 1000  | 23      |
    And there exists a cart with id "5" for user "10" containing items:
      | id | productId | quantity |
      | 1  | 20        | 1        |
    And there exist following promotions:
      | id | type | percentageDiscount | productTypesFilter |
      | 1  | item | 10                 | book               |
      | 2  | item | 20                 | book               |
    And cart "5" has following promotions assigned:
      | promotionId |
      | 1           |
      | 2           |
    When user opens preview of cart "5"
    Then the response should be received with status code 200
    And the response should equal json:
    """
    {
      "itemsTotal": 720,
      "adjustmentsTotal": 0,
      "taxTotal": 165,
      "total": 720,
      "items": [
        {
          "product": {
            "code": "P001",
            "name": "Product 1"
          },
          "unitPrice": 1000,
          "discount": 28,
          "discountValue": 280,
          "distributedOrderDiscountValue": 0,
          "discountedUnitPrice": 720,
          "quantity": 1,
          "total": 720,
          "taxValue": 165
        }
      ]
    }
    """