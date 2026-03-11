# PRI Supplies Inventory System

Based on Official PRI Supplies Inventory 2026 - Digital Twin of the Excel Inventory File

## Overview

The PRI Supplies Inventory System is a digital twin of the official Excel inventory file used by the office. It mirrors the real inventory management workflow including RSMI (Report of Supplies and Materials Issued) and RPCI (Report on Physical Count of Inventories).

## Features

### 1. Dashboard
- Real-time overview of inventory status
- Total Inventory Value calculation
- Top Consuming Division tracking
- Low Stock Alerts
- Monthly Issuance summary

### 2. Divisions / Cost Centers
Official PRI divisions that request and consume supplies:
- **AFS** - Administrative and Finance Section
- **CAD** - Construction and Development
- **ROS** - Regional Operations Section
- **RSEFS** - Research, Extension and Fellowships Section
- **CATS** - Technical Services Section
- **SSS** - Social Security Services
- **RDD** - Research and Development Division

### 3. Inventory Masterlist
Complete master inventory list with:
- Item Name
- Category (Office Supplies, ICT, Janitorial, Semi-Expendable)
- Unit of Measure
- Unit Value
- Total Quantity in Stock
- Total Inventory Value

### 4. Monthly Issuances (RSMI)
Record supplies issued to divisions:
- Select division requesting supplies
- Select inventory item
- Enter quantity issued
- System automatically deducts from stock
- Tracks which division received supplies

### 5. Procurement / Deliveries
Record new supplies arriving in inventory:
- Select inventory item
- Enter quantity delivered
- Confirm delivery record
- System automatically increases stock

### 6. CSV Import
Import inventory data from RPCI CSV file:
- Upload CSV file
- Automatic category mapping
- Updates existing items or creates new ones

## Inventory Categories

1. **Office Supplies (Common)**
   - Bond Paper (A4, Legal Size)
   - Sign Pens, Staple Wires
   - File Folders, Expanding Envelopes

2. **ICT and Computer Supplies**
   - Ink Cartridges, Printer Toners
   - External Hard Drives
   - USB Storage Devices, Computer Mouse

3. **Janitorial and Sanitation Supplies**
   - Alcohol (Gallon, 500ml)
   - Air Freshener, Tissue Paper
   - Cleaning Solutions

4. **Semi-Expendable Property**
   - Heavy Duty Stapler
   - Puncher, Waste Basket

## Data Flow

```
CSV Import → Inventory Masterlist
                     ↓
Procurement → + Stock ← Delivery
                     ↓
Issuance     → - Stock → Division
                     ↓
RSMI Report  ← Monthly Summary
```

## Technical Details

### Database Tables
- `divisions` - Cost centers
- `inventory_categories` - Item categories
- `inventory_master` - Main inventory
- `issuances` - Issue records (RSMI)
- `procurements` - Delivery records

### Key Formulas
- Total Inventory Value = Quantity × Unit Price
- Monthly Issuance Value = Sum of all issuances for the month

## Navigation

The sidebar contains:
- Dashboard
- Divisions / Cost Centers
- Inventory Masterlist
- Monthly Issuances
- Procurement / Deliveries
- Import CSV Data
- Backup Database

## Usage

1. **Initial Setup**: Run the setup script to create tables and default data
2. **Import Data**: Use CSV Import to load initial inventory from RPCI file
3. **Record Deliveries**: Add new stock through Procurement
4. **Record Issuances**: Issue supplies to divisions through Monthly Issuances
5. **Monitor**: Check Dashboard for stock levels and consumption patterns
