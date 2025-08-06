<?php

require "db.php";
require "common_function.php";

$main_categories = [
    [
        'name' => 'Hot Coffee',
    ],
    [
        'name' => 'Cold Coffee'
    ]
];
foreach ($main_categories as $main_category) {
    $insert = insertData('main_categories', $mysqli, $main_category);
    if (!$insert) {
        echo "Error inserting user: " . $mysqli->error;
    } else {
        echo "main categories insert success";
    }
}
$sec_categories = [
    [
        'name'                  => 'Espresso',
        'main_categories_id'    => 1
    ],
    [
        'name'                  => 'Milk-Forward / Specialty',
        'main_categories_id'    => 1
    ],
    [
        'name'                  => 'Alcohol-Infused',
        'main_categories_id'    => 1
    ],
    [
        'name'                  => 'Classic Iced Versions',
        'main_categories_id'    => 2
    ],
    [
        'name'                  => 'Cold Brew Varieties',
        'main_categories_id'    => 2
    ],
    [
        'name'                  => 'Creative / Fusion Iced Coffees',
        'main_categories_id'    => 2
    ],
];
foreach ($sec_categories as $sec_category) {
    $insert = insertData('second_categories', $mysqli, $sec_category);
    if (!$insert) {
        echo "Error inserting user: " . $mysqli->error;
    } else {
        echo "sec categories insert success";
    }
}
// users
$users = [
    [
        'user_name' => 'admin',
        'name' => 'Admin',
        'password' => password_hash('admin', PASSWORD_DEFAULT),
        'status' => 'active',
        'image' => 'default.png',
        'role' => 'admin'
    ],
    [
        'user_name' => 'cashier',
        'name' => 'Cashier',
        'password' => password_hash('cashier', PASSWORD_DEFAULT),
        'status' => 'active',
        'image' => 'default.png',
        'role' => 'cashier'
    ],
    [
        'user_name' => 'waiter',
        'name' => 'Waiter',
        'password' => password_hash('waiter', PASSWORD_DEFAULT),
        'status' => 'active',
        'image' => 'default.png',
        'role' => 'waiter'
    ]
];
foreach ($users as $user) {
    $insert = insertData('user', $mysqli, $user);
    if (!$insert) {
        echo "Error inserting user: " . $mysqli->error;
    } else {
        echo "user insert success";
    }
}

// payment_type
$payment_types = [
    [
        'name'      => "Cash"
    ],
    [
        'name'      => "KBZPay"
    ],
    [
        'name'      => "CB Pay"
    ],
    [
        'name'      => "AYA Pay"
    ],
    [
        'name'      => "WavePay"
    ],
    [
        'name'      => "UAB Pay"
    ],
];
foreach ($payment_types as $payment_type) {
    $insert = insertData('payment_type', $mysqli, $payment_type);
    if (!$insert) {
        echo "Error inserting user: " . $mysqli->error;
    } else {
        echo "payment type insert success";
    }
}

// tables
$tables = [
    [
        'name'      => "A1",
        'status'    => 'available'
    ],
    [
        'name'      => "A2",
        'status'    => 'available'
    ],
    [
        'name'      => "A3",
        'status'    => 'available'
    ],
    [
        'name'      => "A4",
        'status'    => 'available'
    ],
    [
        'name'      => "A5",
        'status'    => 'available'
    ],
    [
        'name'      => "B1",
        'status'    => 'available'
    ],
    [
        'name'      => "B2",
        'status'    => 'available'
    ],
    [
        'name'      => "B3",
        'status'    => 'available'
    ],
    [
        'name'      => "B4",
        'status'    => 'available'
    ],
    [
        'name'      => "B5",
        'status'    => 'available'
    ],
    [
        'name'      => "C1",
        'status'    => 'available'
    ],
    [
        'name'      => "C2",
        'status'    => 'available'
    ],
    [
        'name'      => "C3",
        'status'    => 'available'
    ],
    [
        'name'      => "C4",
        'status'    => 'available'
    ],
    [
        'name'      => "C5",
        'status'    => 'available'
    ],
    [
        'name'      => "D1",
        'status'    => 'available'
    ],
    [
        'name'      => "D2",
        'status'    => 'available'
    ],
    [
        'name'      => "D3",
        'status'    => 'available'
    ],
    [
        'name'      => "D4",
        'status'    => 'available'
    ],
    [
        'name'      => "D5",
        'status'    => 'available'
    ],
];
foreach ($tables as $table) {
    $insert = insertData('tables', $mysqli, $table);
    if (!$insert) {
        echo "Error inserting user: " . $mysqli->error;
    } else {
        echo "tables insert success";
    }
}
