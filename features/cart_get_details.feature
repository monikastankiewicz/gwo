Feature: Calculate cart totals with promotions
  As a user
  I want cart totals to include promotions
  So that I can see the final discounted price

  Scenario: User cart includes item and order promotions
    Given there exist following users:
      | id | name      |
      | 5  | Test User |
    And there exist following products:
      | id | code | name      | type  | price | taxRate |
      | 7  | P001 | Product 1 | book  | 1000  | 23      |
      | 8  | P002 | Product 2 | audio | 500   | 23      |
    And there exists a cart with id "4" for user "5" containing items:
      | id | productId | quantity |
      | 1  | 7         | 1        |
      | 2  | 8         | 1        |
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