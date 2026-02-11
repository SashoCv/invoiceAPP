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
    zip_code: string;
    country: string;
    phone: string;
    email: string;
    website: string;
    tax_number: string;
    bank_name: string;
    bank_account: string;
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
    email: string;
    phone: string;
    address: string;
    city: string;
    zip_code: string;
    country: string;
    tax_number: string;
    notes: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface Article {
    id: number;
    user_id: number;
    name: string;
    description: string;
    unit: string;
    price: number;
    tax_rate: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface InvoiceItem {
    id?: number;
    invoice_id?: number;
    article_id?: number;
    description: string;
    quantity: number;
    unit?: string;
    unit_price: number;
    tax_rate: number;
    tax_amount?: number;
    total: number;
}

export interface Invoice {
    id: number;
    user_id: number;
    client_id: number;
    invoice_number: string;
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
    description: string;
    quantity: number;
    unit?: string;
    unit_price: number;
    tax_rate: number;
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
    description: string;
    quantity: number;
    unit?: string;
    unit_price: number;
    tax_rate: number;
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
