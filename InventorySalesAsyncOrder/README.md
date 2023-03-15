# InventorySalesAsyncOrder module

The `InventorySalesAsyncOrder` module integrates Inventory Sales logic with Async Order module.
It gives ability to use "Use deferred Stock update" config option with async order.

## Usage

| Async Order | Use Deferred Stock Update | Result                                                                                                              |
|-------------|---------------------------|---------------------------------------------------------------------------------------------------------------------|
| 1           | 1                         | Reservation is created when async order is processed by consumer.                                                   |
| 1           | 0                         | Reservation is created before initial async order is placed. Reservation is rolled back if async order is rejected. |
