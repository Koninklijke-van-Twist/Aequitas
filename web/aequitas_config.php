<?php

/**
 * Constants
 */

const AEQUITAS_CACHE_VERSION = 2;
const AEQUITAS_SETTLEMENT_FACTOR = 1.03;
const AEQUITAS_PAGE_SIZE = 200;

const AEQUITAS_ITEMS_ENTITY = 'AppItemCard';
const AEQUITAS_PRICE_LINES_ENTITY = 'Prijslijstregels';

const AEQUITAS_ITEMS_SELECT = 'No,Description,Vendor_No,LVS_Vendor_Name,Last_Direct_Cost,Base_Unit_of_Measure,Blocked';
const AEQUITAS_PRICE_LINES_SELECT = 'Price_List_Code,Line_No,PriceListDescription,Status,Source_Type,Source_No,Asset_Type,Asset_No,Description,Unit_of_Measure_Code,Minimum_Quantity,Amount_Type,DirectUnitCost,Starting_Date,Ending_Date';
