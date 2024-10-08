# Bookly Addon - ARMember booking limits

Limit customer bookings based on the [ARMember](https://www.armemberplugin.com/) subscription plan they own.

If a user has a valid subscription he can book appointments based on the plan limits that are configured in the [functions.php](./functions.php) method.
Any attempt to book outside of the preconfigured plan limits will be considered as over the limit.

## Installation

Download the `.zip` version of this github repository and install it via your WordPress admin plugins section.
Once the plugin is installed, you will need to activate it (ensure bookly and bookly groups plugins are also installed and active prior to this plugin's installation).

Next, follow the [Setup section](#setup) to further configure bookly, in order to make this plugin functional.

## Setup

### Step 1

In order for this plugin to work, you need to manipulate the line that checks the booking limit and apply a WordPress filter to the value before evaluating the conditional statement.

The following diff is based on version `21.3.2` version of [Bookly plugin](https://wordpress.org/plugins/bookly-responsive-appointment-booking-tool/)

```diff
diff --git a/bookly-responsive-appointment-booking-tool/lib/entities/Service.php b/bookly-responsive-appointment-booking-tool/lib/entities/Service.php
index 4304e52..57da1b2 100644
--- a/bookly-responsive-appointment-booking-tool/lib/entities/Service.php
+++ b/bookly-responsive-appointment-booking-tool/lib/entities/Service.php
@@ -369,7 +369,8 @@ class Service extends Lib\Base\Entity
                                 $cart_count ++;
                             }
                         }
-                        if ( $db_count + $cart_count > $this->getAppointmentsLimit() ) {
+                        $limit = apply_filters( 'bookly_appointments_limit', $this->getAppointmentsLimit(), $service_id, $customer_id, $appointment_dates );
++                       if ( $db_count + $cart_count > $limit ) {
                             return true;
                         }
                     }
```

__NOTE:__ Each time the bookly plugin gets updated, we need to re-apply the same diff on that part of the bookly plugins code, otherwise the plugin will not function as expected and default limits will apply to all bookings.
