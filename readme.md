# WooCommerce Mix and Match Products - Subscription Switching

### Quickstart

This is a developmental repo. Clone this repo and run `npm install && npm run build`   
OR    
[Download latest release](https://github.com/kathyisawesome/wc-mnm-subscription-switching/releases/latest)

### What's This?

Experimental mini-extension for [WooCommerce Mix and Match Products](https://woocommerce.com/products/woocommerce-mix-and-match-products/) that enables switching Mix and Match subscription contents in the My Account area and eliminates the need to go through cart/checkout. Note, that because it doesn't go through cart/checkout there are no Switch Orders logged and no way to pro-rate an immediate charge. Any new prices will be charged on the next billing.

![Screen Recording of a "6 Pack of Wine" product in the My Account area. Clicking on the "Change Selections" button reveals a list of wine and the quantities are switch from the currently selected bottles to new bottles. Then "Update subscription" is clicked and the Subscription totals now reflects the new items.](https://user-images.githubusercontent.com/507025/180282841-add5da9e-8755-4567-b1f0-2c63def08b27.gif)

### Important

1. This is provided as is and does not receive priority support.
2. Please test thoroughly before using in production.
3. Requires Mix and Match 2.0.0+
4. No pro-rated payments are collected, and no switch orders are recorded.

### Automatic plugin updates

Plugin updates can be enabled by installing the [Git Updater](https://git-updater.com/) plugin.
