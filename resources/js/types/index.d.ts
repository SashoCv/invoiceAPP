export interface User {
    id: number;
    name: string;
    first_name: string | null;
    last_name: string | null;
    email: string;
    phone: string | null;
    avatar: string | null;
    email_verified_at: string | null;
    invoice_template?: string;
    proforma_template?: string;
    offer_template?: string;
    role: 'user' | 'admin';
    subscription_expires_at: string | null;
    subscription_status?: string;
    days_remaining?: number | null;
    created_at: string;
    updated_at: string;
}

export interface SubscriptionInfo {
    status: 'admin' | 'active' | 'trial' | 'expired';
    isActive: boolean;
    expiresAt: string | null;
    daysRemaining: number | null;
}

export interface Agency {
    id: number;
    user_id: number;
    name: string;
    address: string;
    city: string;
    postal_code: string;
    country: string;
    phone: string;
    email: string;
    website: string;
    tax_number: string;
    registration_number: string;
    logo: string | null;
    display_currency: string;
    created_at: string;
    updated_at: string;
}

export interface BankAccount {
    id: number;
    user_id: number | null;
    agency_id: number | null;
    bank_name: string;
    account_number: string;
    iban: string | null;
    swift: string | null;
    currency: string;
    type: 'denar' | 'foreign';
    is_default: boolean;
    created_at: string;
    updated_at: string;
}

export interface Template {
    name: string;
    description: string;
}

export interface Client {
    id: number;
    user_id: number;
    name: string;
    company?: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    zip_code: string;
    postal_code?: string;
    country: string;
    tax_number: string;
    registration_number?: string;
    bank_name?: string;
    bank_account?: string;
    discount?: number;
    notes: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface Article {
    id: number;
    user_id: number;
    name: string;
    sku: string | null;
    description: string;
    unit: string;
    price: number;
    tax_rate: number;
    is_active: boolean;
    track_inventory: boolean;
    stock_quantity: number;
    low_stock_threshold: number;
    stock_status: 'not_tracked' | 'in_stock' | 'low_stock' | 'out_of_stock';
    created_at: string;
    updated_at: string;
}

export interface Bundle {
    id: number;
    user_id: number;
    name: string;
    description: string | null;
    price: number;
    tax_rate: number;
    is_active: boolean;
    bundle_items?: BundleItem[];
    total_component_price?: number;
    created_at: string;
    updated_at: string;
}

export interface BundleItem {
    id: number;
    bundle_id: number;
    article_id: number;
    quantity: number;
    article?: Article;
    created_at: string;
    updated_at: string;
}

export interface StockMovement {
    id: number;
    user_id: number;
    article_id: number;
    type: 'receipt' | 'issue' | 'adjustment' | 'invoice_deduction' | 'shopify_deduction';
    quantity: number;
    quantity_before: number;
    quantity_after: number;
    reference_type: string | null;
    reference_id: number | null;
    notes: string | null;
    article?: Article;
    created_at: string;
}

export interface InvoiceItem {
    id?: number;
    invoice_id?: number;
    article_id?: number;
    bundle_id?: number | null;
    description: string;
    quantity: number;
    unit?: string;
    unit_price: number;
    tax_rate: number;
    discount?: number;
    tax_amount?: number;
    total: number;
}

export interface Invoice {
    id: number;
    user_id: number;
    client_id: number;
    invoice_number: string;
    invoice_prefix: string | null;
    invoice_sequence: number;
    invoice_year: number;
    issue_date: string;
    due_date: string;
    status: 'draft' | 'sent' | 'unpaid' | 'paid' | 'overdue' | 'cancelled';
    currency: string;
    subtotal: number;
    tax_amount: number;
    total: number;
    notes: string | null;
    client: Client;
    items: InvoiceItem[];
    last_sent_at: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface ProformaInvoiceItem {
    id?: number;
    proforma_invoice_id?: number;
    article_id?: number;
    bundle_id?: number | null;
    description: string;
    quantity: number;
    unit?: string;
    unit_price: number;
    tax_rate: number;
    discount?: number;
    tax_amount?: number;
    total: number;
}

export interface ProformaInvoice {
    id: number;
    user_id: number;
    client_id: number;
    proforma_number: string;
    proforma_prefix: string | null;
    proforma_sequence: number;
    proforma_year: number;
    issue_date: string;
    valid_until: string;
    status: 'draft' | 'sent' | 'converted_to_invoice';
    currency: string;
    subtotal: number;
    tax_amount: number;
    total: number;
    notes: string | null;
    converted_invoice_id: number | null;
    converted_at: string | null;
    client?: Client;
    items?: ProformaInvoiceItem[];
    convertedInvoice?: Invoice;
    user?: User & { agency?: Agency };
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface OfferItem {
    id?: number;
    offer_id?: number;
    article_id?: number;
    bundle_id?: number | null;
    description: string;
    quantity: number;
    unit?: string;
    unit_price: number;
    tax_rate: number;
    discount?: number;
    tax_amount?: number;
    total: number;
}

export interface Offer {
    id: number;
    user_id: number;
    client_id: number;
    offer_number: string;
    offer_prefix: string | null;
    offer_sequence: number;
    offer_year: number;
    title: string;
    content: string | null;
    issue_date: string;
    valid_until: string;
    status: 'draft' | 'sent' | 'accepted' | 'rejected';
    currency: string;
    has_items: boolean;
    subtotal: number;
    tax_amount: number;
    total: number;
    notes: string | null;
    converted_invoice_id: number | null;
    converted_at: string | null;
    accepted_at: string | null;
    rejected_at: string | null;
    client?: Client;
    items?: OfferItem[];
    convertedInvoice?: Invoice;
    user?: User & { agency?: Agency };
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface ExpenseCategory {
    id: number;
    user_id: number;
    name: string;
    color: string | null;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

export interface RecurringExpense {
    id: number;
    user_id: number;
    category_id: number | null;
    name: string;
    description: string | null;
    amount: number;
    day_of_month: number;
    start_date: string | null;
    end_date: string | null;
    is_active: boolean;
    category?: ExpenseCategory;
    created_at: string;
    updated_at: string;
}

export interface Expense {
    id: number;
    user_id: number;
    category_id: number | null;
    recurring_expense_id: number | null;
    name: string;
    description: string | null;
    amount: number;
    date: string;
    category?: ExpenseCategory;
    recurring_expense?: RecurringExpense;
    created_at: string;
    updated_at: string;
}

export interface IncomingInvoiceItem {
    id?: number;
    incoming_invoice_id?: number;
    description: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    tax_amount: number;
    total: number;
}

export interface SpendingAnalysisItem {
    description: string;
    total_amount: number;
    count: number;
}

export interface IncomingInvoice {
    id: number;
    user_id: number;
    client_id: number | null;
    supplier_name: string;
    invoice_number: string | null;
    amount: number;
    currency: string;
    date: string;
    due_date: string | null;
    status: 'unpaid' | 'paid';
    paid_date: string | null;
    notes: string | null;
    client?: Client;
    items?: IncomingInvoiceItem[];
    created_at: string;
    updated_at: string;
}

export interface BankTransaction {
    id: number;
    user_id: number;
    bank_account_id: number | null;
    invoice_id: number | null;
    client_id: number | null;
    type: 'income' | 'expense';
    amount: number;
    currency: string;
    date: string;
    description: string | null;
    reference: string | null;
    batch_id: string;
    bank_account?: BankAccount;
    invoice?: Invoice;
    client?: Client;
    created_at: string;
    updated_at: string;
}

export interface BankTransactionBatch {
    batch_id: string;
    batch_number: number;
    batch_year: number;
    date: string;
    bank_account: BankAccount | null;
    items: BankTransaction[];
}

export interface ClientContract {
    id: number;
    client_id: number;
    title: string;
    file_path: string;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

export interface FlashMessages {
    success?: string;
    error?: string;
}

export interface PageProps {
    auth: {
        user: User;
        isAdmin: boolean;
        subscription: SubscriptionInfo | null;
    };
    impersonating: boolean;
    flash: FlashMessages;
    locale: string;
    translations: Record<string, Record<string, string>>;
}

declare module '@inertiajs/react' {
    export function usePage<T = PageProps>(): {
        props: T & PageProps;
        url: string;
        component: string;
    };
}
