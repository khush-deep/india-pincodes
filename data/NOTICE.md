# PIN code data

Contains data from the "All India Pincode Directory till last month" dataset, published by the Department of Posts,
Ministry of Communications, Government of India, on the Open Government Data Platform India:
https://www.data.gov.in/resource/all-india-pincode-directory-till-last-month

Published under the Government Open Data License - India (GODL-India): https://www.data.gov.in/Godl

Version used: downloaded on 12 Oct 2026 (data.gov.in lists the dataset as last updated on 3 Oct 2025).

The Department of Posts doesn't endorse this package.

Changes: converted to PHP arrays by bin/generate-pincode-data.php. Kept only the PIN, office name, office type,
delivery status, district and state of each office. Removed the office type suffix matching the office's type from office names (i.e. "B.O",
"S.O", also when written twice or without a space) and extra spaces, converted all-capital district names to title case, replaced state names with ISO 3166-2:IN
codes, replaced "NA" with null, and removed duplicate offices.
