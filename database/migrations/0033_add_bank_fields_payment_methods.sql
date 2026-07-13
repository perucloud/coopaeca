-- Metodos de pago: datos bancarios para transferencias (banco, CCI y moneda).
-- El numero de cuenta ya existe como account_number.
ALTER TABLE payment_methods
    ADD COLUMN bank_name VARCHAR(120) NULL AFTER holder_name,
    ADD COLUMN cci VARCHAR(40) NULL AFTER bank_name,
    ADD COLUMN currency VARCHAR(10) NULL AFTER cci;
