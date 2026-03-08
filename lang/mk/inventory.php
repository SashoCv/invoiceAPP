<?php

return [
    'title' => 'Магацин',
    'subtitle' => 'Управувајте со артикли, пакети и движење на залихи',

    // Tabs
    'items_tab' => 'Артикли',
    'bundles_tab' => 'Пакети',
    'movements_tab' => 'Движења',

    // Items
    'new_item' => 'Нов артикл',
    'create_title' => 'Нов магацински артикл',
    'create_subtitle' => 'Додадете нов артикл во магацин',
    'edit_title' => 'Уреди артикл',
    'edit_subtitle' => 'Променете ги деталите за артиклот',
    'item_info' => 'Информации за артикл',
    'stock_info' => 'Информации за залиха',
    'back_to_list' => 'Назад кон магацин',
    'save_item' => 'Зачувај артикл',
    'update_item' => 'Ажурирај артикл',

    // Fields
    'name' => 'Име',
    'sku' => 'SKU',
    'description' => 'Опис',
    'unit' => 'Единица мерка',
    'price' => 'Цена',
    'tax_rate' => 'ДДВ',
    'stock_quantity' => 'Залиха',
    'low_stock_threshold' => 'Праг за ниска залиха',
    'current_stock' => 'Тековна залиха',
    'status' => 'Статус',
    'actions' => 'Акции',
    'initial_stock' => 'Почетна залиха',

    // Status
    'in_stock' => 'Има залиха',
    'low_stock' => 'Ниска залиха',
    'out_of_stock' => 'Нема залиха',
    'active' => 'Активен',
    'inactive' => 'Неактивен',
    'all_statuses' => 'Сите статуси',
    'all_stock_statuses' => 'Сите нивоа на залиха',

    // Filters
    'search' => 'Пребарај',
    'search_placeholder' => 'Име, SKU или опис...',
    'filter' => 'Филтрирај',
    'clear_filters' => 'Исчисти',

    // Actions
    'edit' => 'Уреди',
    'delete' => 'Избриши',
    'view' => 'Прегледај',
    'delete_item' => 'Избриши артикл',
    'delete_confirm' => 'Дали сте сигурни дека сакате да го избришете овој артикл? Историјата на залихи ќе биде зачувана.',
    'adjust_stock' => 'Корекција на залиха',
    'add_stock' => 'Додај залиха',
    'add_to_warehouse' => 'Додај во магацин',
    'select_article' => 'Избери артикл',
    'enable_tracking' => 'Вклучи следење',
    'disable_tracking' => 'Исклучи следење',
    'disable_tracking_confirm' => 'Дали сте сигурни дека сакате да го исклучите следењето за овој артикл? Залихата ќе се ресетира на 0.',

    // Stock adjustment
    'adjustment_type' => 'Тип',
    'receipt' => 'Прием (додај залиха)',
    'issue' => 'Издавање (одземи залиха)',
    'adjustment' => 'Корекција (постави количина)',
    'quantity' => 'Количина',
    'notes' => 'Белешка',
    'notes_placeholder' => 'Причина за корекција...',

    // Bundles
    'new_bundle' => 'Нов пакет',
    'create_bundle' => 'Нов пакет',
    'create_bundle_subtitle' => 'Креирајте пакет од магацински артикли',
    'edit_bundle' => 'Уреди пакет',
    'edit_bundle_subtitle' => 'Променете ги деталите за пакетот',
    'bundle_info' => 'Информации за пакет',
    'bundle_name' => 'Име на пакет',
    'components' => 'Компоненти',
    'component_count' => 'Компоненти',
    'add_component' => 'Додај компонента',
    'select_item' => 'Избери артикл',
    'component_quantity' => 'Кол.',
    'save_bundle' => 'Зачувај пакет',
    'update_bundle' => 'Ажурирај пакет',
    'delete_bundle' => 'Избриши пакет',
    'delete_bundle_confirm' => 'Дали сте сигурни дека сакате да го избришете овој пакет?',
    'no_bundles' => 'Нема пакети',
    'create_first_bundle' => 'Креирајте го вашиот прв пакет за групирање артикли',

    // Movements
    'movement_date' => 'Датум',
    'movement_item' => 'Артикл',
    'movement_type' => 'Тип',
    'movement_quantity' => 'Количина',
    'movement_before' => 'Пред',
    'movement_after' => 'После',
    'movement_notes' => 'Белешка',
    'movement_all_types' => 'Сите типови',
    'movement_from' => 'Од',
    'movement_to' => 'До',
    'type_receipt' => 'Прием',
    'type_issue' => 'Издавање',
    'type_adjustment' => 'Корекција',
    'type_invoice_deduction' => 'Фактура',
    'no_movements' => 'Нема движења на залихи',

    // Empty state
    'no_items' => 'Нема магацински артикли',
    'create_first_description' => 'Додадете го вашиот прв артикл за да почнете со следење на залихи',

    // Show page
    'item_details' => 'Детали за артикл',
    'stock_history' => 'Историја на залихи',

    // Invoice integration
    'inventory_item' => 'Магацински артикл',
    'bundle' => 'Пакет',

    // Dashboard
    'dashboard_title' => 'Магацински преглед',
    'dashboard_subtitle' => 'Залихи, движења и предупредувања на еден поглед',
    'dashboard_total_items' => 'Следени артикли',
    'dashboard_tracked' => 'Артикли со следење',
    'dashboard_stock_value' => 'Вредност на залиха',
    'dashboard_total_value' => 'Вкупна вредност на залихи',
    'dashboard_low_stock' => 'Ниска залиха',
    'dashboard_needs_restock' => 'Потребно дополнување',
    'dashboard_out_of_stock' => 'Нема залиха',
    'dashboard_unavailable' => 'Недостапни артикли',
    'dashboard_monthly_movement' => 'Месечно движење',
    'dashboard_receipts_issues' => 'Прием и издавање во последните 6 месеци',
    'dashboard_receipts' => 'Прием',
    'dashboard_issues' => 'Издавање',
    'dashboard_stock_status' => 'Статус на залиха',
    'dashboard_status_overview' => 'Распределба на нивоа на залиха',
    'dashboard_in_stock_rate' => 'Стапка на залиха',
    'dashboard_alerts' => 'Предупредувања',
    'dashboard_alerts_desc' => 'Артикли кои бараат внимание',
    'dashboard_all_stocked' => 'Сите артикли се со доволна залиха!',
    'dashboard_top_items' => 'Највредни артикли',
    'dashboard_top_items_desc' => 'Артикли со највисока вредност на залиха',
    'dashboard_value' => 'Вредност',
    'dashboard_recent_movements' => 'Последни движења',
    'dashboard_recent_movements_desc' => 'Најнови промени на залихи',
    'dashboard_reference' => 'Фактура',
    'go_to_warehouse' => 'Кон магацин',
    'per_page' => 'По страна',
];
