ALTER TABLE sales
    ADD COLUMN receipt_file_id   INT NULL AFTER voucher_file_id,
    ADD COLUMN receipt_issued_at DATETIME NULL AFTER receipt_file_id,
    ADD COLUMN receipt_issued_by INT NULL AFTER receipt_issued_at;

ALTER TABLE sales
    ADD CONSTRAINT fk_sales_receipt_file FOREIGN KEY (receipt_file_id) REFERENCES files(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_sales_receipt_issued_by FOREIGN KEY (receipt_issued_by) REFERENCES users(id) ON DELETE SET NULL;
