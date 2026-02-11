#!/usr/bin/env python3
"""
Invoice PDF Generator with Cyrillic support
Generates professional PDF invoices/proformas using ReportLab
"""

import json
import sys
import os
from datetime import datetime
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm, cm
from reportlab.platypus import SimpleDocTemplate, Table, TableStyle, Paragraph, Spacer
from reportlab.lib.enums import TA_LEFT, TA_CENTER, TA_RIGHT
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

# Get the directory where this script is located
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
FONTS_DIR = os.path.join(SCRIPT_DIR, 'fonts')

# Register DejaVu fonts for Cyrillic support
try:
    pdfmetrics.registerFont(TTFont('DejaVu', os.path.join(FONTS_DIR, 'DejaVuSans.ttf')))
    pdfmetrics.registerFont(TTFont('DejaVu-Bold', os.path.join(FONTS_DIR, 'DejaVuSans-Bold.ttf')))
    pdfmetrics.registerFont(TTFont('DejaVu-Oblique', os.path.join(FONTS_DIR, 'DejaVuSans-Oblique.ttf')))
    pdfmetrics.registerFont(TTFont('DejaVu-BoldOblique', os.path.join(FONTS_DIR, 'DejaVuSans-BoldOblique.ttf')))

    # Register font family
    from reportlab.pdfbase.pdfmetrics import registerFontFamily
    registerFontFamily('DejaVu',
        normal='DejaVu',
        bold='DejaVu-Bold',
        italic='DejaVu-Oblique',
        boldItalic='DejaVu-BoldOblique'
    )
    FONT_NAME = 'DejaVu'
    FONT_BOLD = 'DejaVu-Bold'
except Exception as e:
    print(f"Warning: Could not load DejaVu fonts: {e}", file=sys.stderr)
    FONT_NAME = 'Helvetica'
    FONT_BOLD = 'Helvetica-Bold'

# Page dimensions
PAGE_WIDTH, PAGE_HEIGHT = A4
MARGIN = 20 * mm

# Colors - matching React preview
PRIMARY_COLOR = colors.HexColor('#1e3a8a')  # blue-900 for stronger contrast
ACCENT_COLOR = colors.HexColor('#1e40af')   # blue-800
SECONDARY_COLOR = colors.HexColor('#6b7280')  # gray-500
BORDER_COLOR = colors.HexColor('#e5e7eb')   # gray-200
HEADER_BG = colors.HexColor('#f9fafb')      # gray-50
TEXT_COLOR = colors.HexColor('#111827')     # gray-900

# Modern template colors
MODERN_PRIMARY = colors.HexColor('#7c3aed')  # Violet
MODERN_SECONDARY = colors.HexColor('#a855f7')  # Purple

def format_number(value, decimals=2):
    """Format number with thousand separators"""
    try:
        num = float(value)
        return f"{num:,.{decimals}f}".replace(",", " ")
    except (ValueError, TypeError):
        return str(value)

def format_date(date_str):
    """Format date string to d.m.Y format"""
    if not date_str:
        return ""
    try:
        if 'T' in str(date_str):
            date_str = str(date_str).split('T')[0]
        dt = datetime.strptime(str(date_str), '%Y-%m-%d')
        return dt.strftime('%d.%m.%Y')
    except:
        return str(date_str)

def get_currency_symbol(currency):
    """Get currency symbol"""
    symbols = {
        'MKD': 'ден.',
        'EUR': '€',
        'USD': '$',
        'GBP': '£',
        'CHF': 'CHF'
    }
    return symbols.get(currency, currency)


class ClassicInvoicePDF:
    """Classic professional invoice template"""

    def __init__(self, data, output_path):
        self.data = data
        self.output_path = output_path
        self.styles = getSampleStyleSheet()
        self._setup_styles()

    def _setup_styles(self):
        """Setup custom paragraph styles with Cyrillic font - matching React preview"""
        self.styles.add(ParagraphStyle(
            name='CompanyName',
            fontSize=14,
            fontName=FONT_BOLD,
            textColor=ACCENT_COLOR,
            spaceAfter=3*mm
        ))
        self.styles.add(ParagraphStyle(
            name='CompanyInfo',
            fontSize=9,
            fontName=FONT_NAME,
            textColor=SECONDARY_COLOR,
            leading=13
        ))
        self.styles.add(ParagraphStyle(
            name='InvoiceTitle',
            fontSize=22,
            fontName=FONT_BOLD,
            textColor=ACCENT_COLOR,
            alignment=TA_RIGHT,
            spaceAfter=2*mm
        ))
        self.styles.add(ParagraphStyle(
            name='InvoiceNumber',
            fontSize=10,
            fontName=FONT_NAME,
            textColor=SECONDARY_COLOR,
            alignment=TA_RIGHT,
            spaceBefore=1*mm
        ))
        self.styles.add(ParagraphStyle(
            name='SectionTitle',
            fontSize=9,
            fontName=FONT_BOLD,
            textColor=ACCENT_COLOR,
            spaceBefore=0,
            spaceAfter=3*mm,
            textTransform='uppercase'
        ))
        self.styles.add(ParagraphStyle(
            name='ClientName',
            fontSize=11,
            fontName=FONT_BOLD,
            textColor=TEXT_COLOR
        ))
        self.styles.add(ParagraphStyle(
            name='ClientInfo',
            fontSize=9,
            fontName=FONT_NAME,
            textColor=SECONDARY_COLOR,
            leading=13
        ))
        self.styles.add(ParagraphStyle(
            name='Notes',
            fontSize=9,
            fontName=FONT_NAME,
            textColor=SECONDARY_COLOR,
            leading=13
        ))
        self.styles.add(ParagraphStyle(
            name='Footer',
            fontSize=8,
            fontName=FONT_NAME,
            textColor=colors.HexColor('#9ca3af'),  # gray-400
            alignment=TA_CENTER
        ))
        self.styles.add(ParagraphStyle(
            name='TableCell',
            fontSize=9,
            fontName=FONT_NAME,
            textColor=TEXT_COLOR,
            leading=13
        ))

    def generate(self):
        """Generate the PDF document"""
        doc = SimpleDocTemplate(
            self.output_path,
            pagesize=A4,
            rightMargin=MARGIN,
            leftMargin=MARGIN,
            topMargin=MARGIN,
            bottomMargin=MARGIN
        )

        elements = []

        # Header with company info and invoice title
        elements.extend(self._build_header())
        elements.append(Spacer(1, 5*mm))

        # Client info and invoice details
        elements.extend(self._build_info_section())
        elements.append(Spacer(1, 8*mm))

        # Items table
        elements.extend(self._build_items_table())
        elements.append(Spacer(1, 5*mm))

        # Totals
        elements.extend(self._build_totals())
        elements.append(Spacer(1, 8*mm))

        # Bank account
        elements.extend(self._build_bank_info())

        # Notes
        if self.data.get('notes'):
            elements.append(Spacer(1, 5*mm))
            elements.extend(self._build_notes())

        # Build PDF
        doc.build(elements, onFirstPage=self._add_footer, onLaterPages=self._add_footer)

    def _build_header(self):
        """Build header section with company info and invoice title"""
        elements = []

        agency = self.data.get('agency', {})
        doc_type = self.data.get('type', 'invoice')
        doc_number = self.data.get('number', '')

        titles = {
            'invoice': 'ФАКТУРА',
            'proforma': 'ПРОФАКТУРА',
            'offer': 'ПОНУДА'
        }
        title = titles.get(doc_type, 'ФАКТУРА')

        # Left side: Company info
        company_info = []
        if agency.get('name'):
            company_info.append(Paragraph(agency['name'], self.styles['CompanyName']))

        info_lines = []
        if agency.get('address'):
            info_lines.append(agency['address'])
        if agency.get('city'):
            city_line = agency['city']
            if agency.get('postal_code'):
                city_line = f"{agency['postal_code']} {city_line}"
            info_lines.append(city_line)
        if agency.get('phone'):
            info_lines.append(f"Тел: {agency['phone']}")
        if agency.get('email'):
            info_lines.append(f"Email: {agency['email']}")
        if agency.get('tax_number'):
            info_lines.append(f"ЕДБ: {agency['tax_number']}")
        if agency.get('registration_number'):
            info_lines.append(f"ЕМБС: {agency['registration_number']}")

        if info_lines:
            company_info.append(Paragraph('<br/>'.join(info_lines), self.styles['CompanyInfo']))

        # Right side: Invoice title and number
        invoice_info = []
        invoice_info.append(Paragraph(title, self.styles['InvoiceTitle']))
        invoice_info.append(Paragraph(f"Бр. {doc_number}", self.styles['InvoiceNumber']))

        # Create header table
        header_data = [[
            company_info,
            invoice_info
        ]]

        header_table = Table(header_data, colWidths=[90*mm, 80*mm])
        header_table.setStyle(TableStyle([
            ('VALIGN', (0, 0), (-1, -1), 'TOP'),
            ('ALIGN', (1, 0), (1, 0), 'RIGHT'),
        ]))

        elements.append(header_table)

        # Add separator line - matching React's border-b-2 border-blue-800
        elements.append(Spacer(1, 4*mm))
        separator_data = [['']]
        separator = Table(separator_data, colWidths=[170*mm])
        separator.setStyle(TableStyle([
            ('LINEBELOW', (0, 0), (-1, -1), 2, ACCENT_COLOR),
        ]))
        elements.append(separator)

        return elements

    def _build_info_section(self):
        """Build client and invoice details section"""
        elements = []

        client = self.data.get('client', {})

        # Client info (left)
        client_content = []
        client_content.append(Paragraph('КЛИЕНТ', self.styles['SectionTitle']))

        client_name = client.get('company') or client.get('name', '')
        client_content.append(Paragraph(client_name, self.styles['ClientName']))

        client_details = []
        if client.get('address'):
            client_details.append(client['address'])
        if client.get('city'):
            city_line = client['city']
            if client.get('postal_code'):
                city_line = f"{client['postal_code']} {city_line}"
            client_details.append(city_line)
        if client.get('email'):
            client_details.append(client['email'])
        if client.get('tax_number'):
            client_details.append(f"ЕДБ: {client['tax_number']}")
        if client.get('registration_number'):
            client_details.append(f"ЕМБС: {client['registration_number']}")

        if client_details:
            client_content.append(Paragraph('<br/>'.join(client_details), self.styles['ClientInfo']))

        # Invoice details (right)
        details_content = []
        details_content.append(Paragraph('ДЕТАЛИ', self.styles['SectionTitle']))

        details_data = []
        details_data.append(['Датум на издавање:', format_date(self.data.get('issue_date'))])

        if self.data.get('due_date'):
            details_data.append(['Датум на доспевање:', format_date(self.data.get('due_date'))])
        if self.data.get('valid_until'):
            details_data.append(['Важи до:', format_date(self.data.get('valid_until'))])

        details_data.append(['Валута:', self.data.get('currency', 'MKD')])
        details_data.append(['Статус:', self._get_status_label()])

        details_table = Table(details_data, colWidths=[45*mm, 40*mm])
        details_table.setStyle(TableStyle([
            ('FONTNAME', (0, 0), (0, -1), FONT_NAME),
            ('FONTNAME', (1, 0), (1, -1), FONT_BOLD),
            ('FONTSIZE', (0, 0), (-1, -1), 10),
            ('TEXTCOLOR', (0, 0), (0, -1), SECONDARY_COLOR),
            ('ALIGN', (0, 0), (0, -1), 'LEFT'),
            ('ALIGN', (1, 0), (1, -1), 'LEFT'),
            ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
        ]))
        details_content.append(details_table)

        # Combine into two-column layout
        info_data = [[client_content, details_content]]
        info_table = Table(info_data, colWidths=[90*mm, 80*mm])
        info_table.setStyle(TableStyle([
            ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ]))

        elements.append(info_table)
        return elements

    def _get_status_label(self):
        """Get localized status label"""
        status = self.data.get('status', 'draft')
        labels = {
            'draft': 'Нацрт',
            'sent': 'Испратена',
            'unpaid': 'Неплатена',
            'paid': 'Платена',
            'overdue': 'Задоцнета',
            'cancelled': 'Откажана',
            'converted_to_invoice': 'Конвертирана',
            'accepted': 'Прифатена',
            'rejected': 'Одбиена'
        }
        return labels.get(status, status)

    def _build_items_table(self):
        """Build items table"""
        elements = []

        currency = self.data.get('currency', 'MKD')
        currency_symbol = get_currency_symbol(currency)

        # Table header
        header = ['Опис', 'Количина', 'Цена', 'ДДВ %', 'Вкупно']

        # Table data
        table_data = [header]

        for item in self.data.get('items', []):
            qty = float(item.get('quantity', 0))
            price = float(item.get('unit_price', 0))
            tax_rate = float(item.get('tax_rate', 0))

            subtotal = qty * price
            tax = subtotal * (tax_rate / 100)
            total = subtotal + tax

            row = [
                Paragraph(str(item.get('description', '')), self.styles['TableCell']),
                format_number(qty),
                format_number(price),
                f"{format_number(tax_rate, 0)}%",
                f"{format_number(total)} {currency_symbol}"
            ]
            table_data.append(row)

        # Create table
        col_widths = [75*mm, 22*mm, 25*mm, 20*mm, 28*mm]
        items_table = Table(table_data, colWidths=col_widths, repeatRows=1)

        items_table.setStyle(TableStyle([
            # Header style - matching React's bg-blue-800
            ('BACKGROUND', (0, 0), (-1, 0), ACCENT_COLOR),
            ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
            ('FONTNAME', (0, 0), (-1, 0), FONT_BOLD),
            ('FONTSIZE', (0, 0), (-1, 0), 9),
            ('BOTTOMPADDING', (0, 0), (-1, 0), 10),
            ('TOPPADDING', (0, 0), (-1, 0), 10),
            ('LEFTPADDING', (0, 0), (-1, 0), 10),
            ('RIGHTPADDING', (0, 0), (-1, 0), 10),

            # Body style
            ('FONTNAME', (0, 1), (-1, -1), FONT_NAME),
            ('FONTSIZE', (0, 1), (-1, -1), 9),
            ('BOTTOMPADDING', (0, 1), (-1, -1), 8),
            ('TOPPADDING', (0, 1), (-1, -1), 8),
            ('LEFTPADDING', (0, 1), (-1, -1), 10),
            ('RIGHTPADDING', (0, 1), (-1, -1), 10),

            # Alignment
            ('ALIGN', (1, 0), (-1, -1), 'RIGHT'),
            ('ALIGN', (0, 0), (0, -1), 'LEFT'),
            ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),

            # Borders - subtle bottom borders
            ('LINEBELOW', (0, 1), (-1, -1), 0.5, BORDER_COLOR),

            # Alternating row colors - matching React's bg-gray-50
            ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, HEADER_BG]),
        ]))

        elements.append(items_table)
        return elements

    def _build_totals(self):
        """Build totals section"""
        elements = []

        currency = self.data.get('currency', 'MKD')
        currency_symbol = get_currency_symbol(currency)

        subtotal = float(self.data.get('subtotal', 0))
        tax_amount = float(self.data.get('tax_amount', 0))
        total = float(self.data.get('total', 0))

        totals_data = [
            ['Меѓузбир:', f"{format_number(subtotal)} {currency_symbol}"],
            ['ДДВ:', f"{format_number(tax_amount)} {currency_symbol}"],
            ['ВКУПНО:', f"{format_number(total)} {currency_symbol}"],
        ]

        totals_table = Table(totals_data, colWidths=[120*mm, 50*mm])
        totals_table.setStyle(TableStyle([
            ('ALIGN', (0, 0), (0, -1), 'RIGHT'),
            ('ALIGN', (1, 0), (1, -1), 'RIGHT'),
            ('FONTNAME', (0, 0), (-1, 1), FONT_NAME),
            ('FONTNAME', (0, 2), (-1, 2), FONT_BOLD),
            ('FONTSIZE', (0, 0), (-1, 1), 9),
            ('FONTSIZE', (0, 2), (-1, 2), 11),
            ('TEXTCOLOR', (0, 0), (-1, 1), SECONDARY_COLOR),
            ('TEXTCOLOR', (0, 2), (-1, 2), ACCENT_COLOR),
            ('TOPPADDING', (0, 0), (-1, -1), 3),
            ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
            ('TOPPADDING', (0, 2), (-1, 2), 8),
            ('LINEABOVE', (0, 2), (-1, 2), 2, ACCENT_COLOR),
        ]))

        elements.append(totals_table)
        return elements

    def _build_bank_info(self):
        """Build bank account information"""
        elements = []

        bank = self.data.get('bank_account')
        if not bank:
            return elements

        elements.append(Paragraph('БАНКАРСКА СМЕТКА', self.styles['SectionTitle']))

        bank_info = []
        bank_info.append(f"<b>{bank.get('bank_name', '')}</b>")
        bank_info.append(f"Сметка: {bank.get('account_number', '')}")
        if bank.get('iban'):
            bank_info.append(f"IBAN: {bank['iban']}")
        if bank.get('swift'):
            bank_info.append(f"SWIFT: {bank['swift']}")

        elements.append(Paragraph('<br/>'.join(bank_info), self.styles['ClientInfo']))
        return elements

    def _build_notes(self):
        """Build notes section"""
        elements = []

        elements.append(Paragraph('ЗАБЕЛЕШКИ', self.styles['SectionTitle']))
        elements.append(Paragraph(self.data.get('notes', ''), self.styles['Notes']))

        return elements

    def _add_footer(self, canvas, doc):
        """Add footer to each page"""
        canvas.saveState()

        agency = self.data.get('agency', {})
        footer_text = []

        if agency.get('name'):
            footer_text.append(agency['name'])
        if agency.get('website'):
            footer_text.append(agency['website'])
        if agency.get('email'):
            footer_text.append(agency['email'])

        if footer_text:
            canvas.setFont(FONT_NAME, 8)
            canvas.setFillColor(SECONDARY_COLOR)
            canvas.drawCentredString(PAGE_WIDTH / 2, 15 * mm, ' | '.join(footer_text))

        canvas.restoreState()


class ModernInvoicePDF(ClassicInvoicePDF):
    """Modern invoice template with gradient-like styling"""

    def _setup_styles(self):
        """Setup modern styles"""
        super()._setup_styles()

        # Override with modern colors
        self.styles['CompanyName'].textColor = colors.white
        self.styles['InvoiceTitle'].textColor = colors.white
        self.styles['InvoiceNumber'].textColor = colors.white
        self.styles['SectionTitle'].textColor = MODERN_PRIMARY
        self.styles['SectionTitle'].fontSize = 8

    def _build_header(self):
        """Build modern header with colored background"""
        elements = []

        agency = self.data.get('agency', {})
        doc_type = self.data.get('type', 'invoice')
        doc_number = self.data.get('number', '')

        titles = {
            'invoice': 'ФАКТУРА',
            'proforma': 'ПРОФАКТУРА',
            'offer': 'ПОНУДА'
        }
        title = titles.get(doc_type, 'ФАКТУРА')

        # Create header content
        company_lines = []
        if agency.get('name'):
            company_lines.append(agency['name'])
        if agency.get('address'):
            company_lines.append(agency['address'])
        if agency.get('city'):
            company_lines.append(agency['city'])
        if agency.get('email'):
            company_lines.append(agency['email'])

        header_data = [[
            '\n'.join(company_lines) if company_lines else '',
            f"{title}\n\n{doc_number}"
        ]]

        header_table = Table(header_data, colWidths=[90*mm, 80*mm])
        header_table.setStyle(TableStyle([
            ('BACKGROUND', (0, 0), (-1, -1), MODERN_PRIMARY),
            ('TEXTCOLOR', (0, 0), (-1, -1), colors.white),
            ('FONTNAME', (0, 0), (0, 0), FONT_NAME),
            ('FONTNAME', (1, 0), (1, 0), FONT_BOLD),
            ('FONTSIZE', (0, 0), (0, 0), 10),
            ('FONTSIZE', (1, 0), (1, 0), 16),
            ('ALIGN', (0, 0), (0, 0), 'LEFT'),
            ('ALIGN', (1, 0), (1, 0), 'RIGHT'),
            ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
            ('TOPPADDING', (0, 0), (-1, -1), 18),
            ('BOTTOMPADDING', (0, 0), (-1, -1), 18),
            ('LEFTPADDING', (0, 0), (-1, -1), 15),
            ('RIGHTPADDING', (0, 0), (-1, -1), 15),
            ('LEADING', (1, 0), (1, 0), 22),
        ]))

        elements.append(header_table)
        return elements

    def _build_info_section(self):
        """Build modern info section with card-like boxes"""
        elements = []

        client = self.data.get('client', {})
        currency = self.data.get('currency', 'MKD')
        currency_symbol = get_currency_symbol(currency)
        total = float(self.data.get('total', 0))

        # Card 1: Client
        client_name = client.get('company') or client.get('name', '')
        client_address = client.get('address', '')
        client_city = client.get('city', '')

        # Card 2: Date
        issue_date = format_date(self.data.get('issue_date'))
        due_date = self.data.get('due_date') or self.data.get('valid_until')
        due_label = 'Доспева' if self.data.get('type') == 'invoice' else 'Важи до'

        # Card 3: Amount
        total_formatted = format_number(total)

        # Build 3 cards in a row
        card_style = TableStyle([
            ('BACKGROUND', (0, 0), (-1, -1), colors.white),
            ('BOX', (0, 0), (-1, -1), 1, BORDER_COLOR),
            ('TOPPADDING', (0, 0), (-1, -1), 12),
            ('BOTTOMPADDING', (0, 0), (-1, -1), 12),
            ('LEFTPADDING', (0, 0), (-1, -1), 12),
            ('RIGHTPADDING', (0, 0), (-1, -1), 12),
            ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ])

        # Card 1 content
        card1_content = []
        card1_content.append(Paragraph('<font color="#7c3aed" size="8"><b>КЛИЕНТ</b></font>', self.styles['Normal']))
        card1_content.append(Spacer(1, 2*mm))
        card1_content.append(Paragraph(f'<b>{client_name}</b>', self.styles['Normal']))
        if client_address:
            card1_content.append(Paragraph(f'<font color="#6b7280" size="9">{client_address}</font>', self.styles['Normal']))
        if client_city:
            card1_content.append(Paragraph(f'<font color="#6b7280" size="9">{client_city}</font>', self.styles['Normal']))

        card1 = Table([[card1_content]], colWidths=[53*mm])
        card1.setStyle(card_style)

        # Card 2 content
        card2_content = []
        card2_content.append(Paragraph('<font color="#7c3aed" size="8"><b>ДАТУМ</b></font>', self.styles['Normal']))
        card2_content.append(Spacer(1, 2*mm))
        card2_content.append(Paragraph(f'<b>{issue_date}</b>', self.styles['Normal']))
        if due_date:
            card2_content.append(Paragraph(f'<font color="#6b7280" size="9">{due_label}: {format_date(due_date)}</font>', self.styles['Normal']))

        card2 = Table([[card2_content]], colWidths=[53*mm])
        card2.setStyle(card_style)

        # Card 3 content
        card3_content = []
        card3_content.append(Paragraph('<font color="#7c3aed" size="8"><b>ИЗНОС</b></font>', self.styles['Normal']))
        card3_content.append(Spacer(1, 2*mm))
        card3_content.append(Paragraph(f'<font size="14"><b>{total_formatted}</b></font>', self.styles['Normal']))
        card3_content.append(Paragraph(f'<font color="#6b7280" size="9">{currency_symbol}</font>', self.styles['Normal']))

        card3 = Table([[card3_content]], colWidths=[53*mm])
        card3.setStyle(card_style)

        # Combine cards in a row
        cards_row = Table([[card1, card2, card3]], colWidths=[57*mm, 57*mm, 57*mm])
        cards_row.setStyle(TableStyle([
            ('VALIGN', (0, 0), (-1, -1), 'TOP'),
            ('LEFTPADDING', (0, 0), (-1, -1), 0),
            ('RIGHTPADDING', (0, 0), (-1, -1), 0),
        ]))

        elements.append(cards_row)
        return elements

    def _build_items_table(self):
        """Build items table with modern styling in a card"""
        elements = []

        currency = self.data.get('currency', 'MKD')
        currency_symbol = get_currency_symbol(currency)

        header = ['Опис', 'Кол.', 'Цена', 'ДДВ', 'Вкупно']
        table_data = [header]

        for item in self.data.get('items', []):
            qty = float(item.get('quantity', 0))
            price = float(item.get('unit_price', 0))
            tax_rate = float(item.get('tax_rate', 0))

            subtotal = qty * price
            tax = subtotal * (tax_rate / 100)
            total = subtotal + tax

            row = [
                Paragraph(str(item.get('description', '')), self.styles['TableCell']),
                format_number(qty),
                format_number(price),
                f"{format_number(tax_rate, 0)}%",
                format_number(total)
            ]
            table_data.append(row)

        col_widths = [75*mm, 20*mm, 25*mm, 20*mm, 30*mm]
        items_table = Table(table_data, colWidths=col_widths, repeatRows=1)

        items_table.setStyle(TableStyle([
            # Header - purple gradient simulation
            ('BACKGROUND', (0, 0), (-1, 0), MODERN_PRIMARY),
            ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
            ('FONTNAME', (0, 0), (-1, 0), FONT_BOLD),
            ('FONTSIZE', (0, 0), (-1, 0), 9),
            ('BOTTOMPADDING', (0, 0), (-1, 0), 12),
            ('TOPPADDING', (0, 0), (-1, 0), 12),
            ('LEFTPADDING', (0, 0), (-1, 0), 15),
            ('RIGHTPADDING', (0, 0), (-1, 0), 15),

            # Body
            ('BACKGROUND', (0, 1), (-1, -1), colors.white),
            ('FONTNAME', (0, 1), (-1, -1), FONT_NAME),
            ('FONTSIZE', (0, 1), (-1, -1), 9),
            ('BOTTOMPADDING', (0, 1), (-1, -1), 10),
            ('TOPPADDING', (0, 1), (-1, -1), 10),
            ('LEFTPADDING', (0, 1), (-1, -1), 15),
            ('RIGHTPADDING', (0, 1), (-1, -1), 15),

            # Alignment
            ('ALIGN', (1, 0), (-1, -1), 'RIGHT'),
            ('ALIGN', (0, 0), (0, -1), 'LEFT'),
            ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),

            # Borders - card style
            ('BOX', (0, 0), (-1, -1), 1, BORDER_COLOR),
            ('LINEBELOW', (0, 1), (-1, -2), 0.5, colors.HexColor('#f3f4f6')),
            ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor('#faf5ff')]),
        ]))

        elements.append(items_table)
        return elements

    def _build_totals(self):
        """Build totals with modern styling"""
        elements = []

        currency = self.data.get('currency', 'MKD')
        currency_symbol = get_currency_symbol(currency)

        subtotal = float(self.data.get('subtotal', 0))
        tax_amount = float(self.data.get('tax_amount', 0))
        total = float(self.data.get('total', 0))

        totals_data = [
            ['Меѓузбир', f"{format_number(subtotal)} {currency_symbol}"],
            ['ДДВ', f"{format_number(tax_amount)} {currency_symbol}"],
            ['Вкупно', f"{format_number(total)} {currency_symbol}"],
        ]

        totals_table = Table(totals_data, colWidths=[120*mm, 50*mm])
        totals_table.setStyle(TableStyle([
            ('ALIGN', (0, 0), (0, -1), 'RIGHT'),
            ('ALIGN', (1, 0), (1, -1), 'RIGHT'),
            ('FONTNAME', (0, 0), (-1, 1), FONT_NAME),
            ('FONTNAME', (0, 2), (-1, 2), FONT_BOLD),
            ('FONTSIZE', (0, 0), (-1, 1), 9),
            ('FONTSIZE', (0, 2), (-1, 2), 12),
            ('TEXTCOLOR', (0, 0), (-1, 1), SECONDARY_COLOR),
            ('TEXTCOLOR', (0, 2), (-1, 2), MODERN_PRIMARY),
            ('TOPPADDING', (0, 0), (-1, -1), 4),
            ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
            ('TOPPADDING', (0, 2), (-1, 2), 8),
            ('LINEABOVE', (0, 2), (-1, 2), 2, MODERN_SECONDARY),
        ]))

        elements.append(totals_table)
        return elements


class MinimalInvoicePDF(ClassicInvoicePDF):
    """Minimal elegant invoice template"""

    def _setup_styles(self):
        """Setup minimal styles"""
        self.styles = getSampleStyleSheet()

        self.styles.add(ParagraphStyle(
            name='CompanyName',
            fontSize=14,
            fontName=FONT_NAME,
            textColor=colors.HexColor('#374151'),
            spaceAfter=2*mm
        ))
        self.styles.add(ParagraphStyle(
            name='CompanyInfo',
            fontSize=9,
            fontName=FONT_NAME,
            textColor=colors.HexColor('#9ca3af'),
            leading=12
        ))
        self.styles.add(ParagraphStyle(
            name='InvoiceTitle',
            fontSize=10,
            fontName=FONT_NAME,
            textColor=colors.HexColor('#9ca3af'),
            alignment=TA_RIGHT,
            spaceAfter=3*mm
        ))
        self.styles.add(ParagraphStyle(
            name='InvoiceNumber',
            fontSize=18,
            fontName=FONT_NAME,
            textColor=colors.HexColor('#374151'),
            alignment=TA_RIGHT,
            spaceBefore=2*mm
        ))
        self.styles.add(ParagraphStyle(
            name='SectionTitle',
            fontSize=8,
            fontName=FONT_NAME,
            textColor=colors.HexColor('#9ca3af'),
            spaceBefore=0,
            spaceAfter=3*mm
        ))
        self.styles.add(ParagraphStyle(
            name='ClientName',
            fontSize=11,
            fontName=FONT_NAME,
            textColor=colors.HexColor('#374151')
        ))
        self.styles.add(ParagraphStyle(
            name='ClientInfo',
            fontSize=9,
            fontName=FONT_NAME,
            textColor=colors.HexColor('#9ca3af'),
            leading=14
        ))
        self.styles.add(ParagraphStyle(
            name='Notes',
            fontSize=9,
            fontName=FONT_NAME,
            textColor=colors.HexColor('#6b7280'),
            leading=12
        ))
        self.styles.add(ParagraphStyle(
            name='Footer',
            fontSize=8,
            fontName=FONT_NAME,
            textColor=colors.HexColor('#d1d5db'),
            alignment=TA_CENTER
        ))
        self.styles.add(ParagraphStyle(
            name='TableCell',
            fontSize=10,
            fontName=FONT_NAME,
            textColor=colors.HexColor('#374151'),
            leading=14
        ))

    def _build_header(self):
        """Build minimal header"""
        elements = []

        agency = self.data.get('agency', {})
        doc_type = self.data.get('type', 'invoice')
        doc_number = self.data.get('number', '')

        titles = {
            'invoice': 'ФАКТУРА',
            'proforma': 'ПРОФАКТУРА',
            'offer': 'ПОНУДА'
        }
        title = titles.get(doc_type, 'ФАКТУРА')

        # Left side
        company_info = []
        if agency.get('name'):
            company_info.append(Paragraph(agency['name'], self.styles['CompanyName']))

        info_lines = []
        if agency.get('address'):
            info_lines.append(agency['address'])
        if agency.get('city'):
            info_lines.append(agency['city'])
        if agency.get('tax_number'):
            info_lines.append(f"ЕДБ {agency['tax_number']}")

        if info_lines:
            company_info.append(Paragraph('<br/>'.join(info_lines), self.styles['CompanyInfo']))

        # Right side
        invoice_info = []
        invoice_info.append(Paragraph(title, self.styles['InvoiceTitle']))
        invoice_info.append(Paragraph(doc_number, self.styles['InvoiceNumber']))

        header_data = [[company_info, invoice_info]]

        header_table = Table(header_data, colWidths=[90*mm, 80*mm])
        header_table.setStyle(TableStyle([
            ('VALIGN', (0, 0), (-1, -1), 'TOP'),
            ('ALIGN', (1, 0), (1, 0), 'RIGHT'),
        ]))

        elements.append(header_table)
        elements.append(Spacer(1, 5*mm))

        return elements

    def _build_items_table(self):
        """Build minimal items table"""
        elements = []

        currency = self.data.get('currency', 'MKD')
        currency_symbol = get_currency_symbol(currency)

        header = ['Опис', 'Кол.', 'Цена', 'Износ']
        table_data = [header]

        for item in self.data.get('items', []):
            qty = float(item.get('quantity', 0))
            price = float(item.get('unit_price', 0))
            tax_rate = float(item.get('tax_rate', 0))

            subtotal = qty * price
            tax = subtotal * (tax_rate / 100)
            total = subtotal + tax

            row = [
                Paragraph(str(item.get('description', '')), self.styles['TableCell']),
                format_number(qty, 0),
                format_number(price),
                format_number(total)
            ]
            table_data.append(row)

        col_widths = [95*mm, 20*mm, 27*mm, 28*mm]
        items_table = Table(table_data, colWidths=col_widths, repeatRows=1)

        items_table.setStyle(TableStyle([
            # Header
            ('FONTNAME', (0, 0), (-1, 0), FONT_NAME),
            ('FONTSIZE', (0, 0), (-1, 0), 8),
            ('TEXTCOLOR', (0, 0), (-1, 0), colors.HexColor('#9ca3af')),
            ('BOTTOMPADDING', (0, 0), (-1, 0), 10),
            ('TOPPADDING', (0, 0), (-1, 0), 5),
            ('LINEBELOW', (0, 0), (-1, 0), 0.5, colors.HexColor('#e5e7eb')),

            # Body
            ('FONTNAME', (0, 1), (-1, -1), FONT_NAME),
            ('FONTSIZE', (0, 1), (-1, -1), 10),
            ('TEXTCOLOR', (0, 1), (0, -1), colors.HexColor('#374151')),
            ('TEXTCOLOR', (1, 1), (-1, -1), colors.HexColor('#6b7280')),
            ('BOTTOMPADDING', (0, 1), (-1, -1), 12),
            ('TOPPADDING', (0, 1), (-1, -1), 12),
            ('LINEBELOW', (0, 1), (-1, -1), 0.25, colors.HexColor('#f3f4f6')),

            # Alignment
            ('ALIGN', (1, 0), (-1, -1), 'RIGHT'),
            ('ALIGN', (0, 0), (0, -1), 'LEFT'),
            ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ]))

        elements.append(items_table)
        return elements

    def _build_totals(self):
        """Build minimal totals"""
        elements = []

        currency = self.data.get('currency', 'MKD')
        currency_symbol = get_currency_symbol(currency)

        subtotal = float(self.data.get('subtotal', 0))
        tax_amount = float(self.data.get('tax_amount', 0))
        total = float(self.data.get('total', 0))

        totals_data = [
            ['Меѓузбир', format_number(subtotal)],
            ['ДДВ', format_number(tax_amount)],
            ['Вкупно', f"{format_number(total)} {currency_symbol}"],
        ]

        totals_table = Table(totals_data, colWidths=[130*mm, 40*mm])
        totals_table.setStyle(TableStyle([
            ('ALIGN', (0, 0), (0, -1), 'RIGHT'),
            ('ALIGN', (1, 0), (1, -1), 'RIGHT'),
            ('FONTNAME', (0, 0), (-1, -1), FONT_NAME),
            ('FONTSIZE', (0, 0), (-1, 1), 9),
            ('FONTSIZE', (0, 2), (0, 2), 8),
            ('FONTSIZE', (1, 2), (1, 2), 16),
            ('TEXTCOLOR', (0, 0), (-1, 1), colors.HexColor('#9ca3af')),
            ('TEXTCOLOR', (0, 2), (0, 2), colors.HexColor('#9ca3af')),
            ('TEXTCOLOR', (1, 2), (1, 2), colors.HexColor('#374151')),
            ('TOPPADDING', (0, 2), (-1, 2), 12),
            ('LINEABOVE', (1, 2), (1, 2), 1, colors.HexColor('#374151')),
        ]))

        elements.append(totals_table)
        return elements


def main():
    """Main entry point"""
    if len(sys.argv) < 3:
        print("Usage: generate_pdf.py <json_data_file> <output_pdf_path>", file=sys.stderr)
        sys.exit(1)

    json_file = sys.argv[1]
    output_path = sys.argv[2]

    # Read JSON data
    try:
        with open(json_file, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print(f"Error reading JSON file: {e}", file=sys.stderr)
        sys.exit(1)

    # Get template type
    template = data.get('template', 'classic')

    # Generate PDF based on template
    try:
        if template == 'modern':
            pdf = ModernInvoicePDF(data, output_path)
        elif template == 'minimal':
            pdf = MinimalInvoicePDF(data, output_path)
        else:
            pdf = ClassicInvoicePDF(data, output_path)

        pdf.generate()
        print(f"PDF generated successfully: {output_path}")
    except Exception as e:
        print(f"Error generating PDF: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == '__main__':
    main()
