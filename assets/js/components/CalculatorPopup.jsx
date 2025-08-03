import React, { useState, useEffect } from "react";
import CurrencyFlag from "./CurrencyFlag";

const CalculatorPopup = ({
  isOpen,
  onClose,
  fromCurrency = "USD",
  toCurrency = "PLN",
  currencies = [],
}) => {
  const [fromAmount, setFromAmount] = useState("");
  const [toAmount, setToAmount] = useState("");
  const [fullPrecisionAmount, setFullPrecisionAmount] = useState("");

  const [selectedFrom, setSelectedFrom] = useState(fromCurrency);
  const [selectedTo, setSelectedTo] = useState(toCurrency);
  const [operationType, setOperationType] = useState("mid");

  const [error, setError] = useState("");

  useEffect(() => {
    if (isOpen) {
      setSelectedFrom(fromCurrency);
      setSelectedTo(toCurrency);
    }
  }, [isOpen, fromCurrency, toCurrency]);

  useEffect(() => {
    if (fromAmount && selectedFrom && selectedTo) {
      const timeoutId = setTimeout(() => {
        handleConvert();
      }, 300); // Debounce 300ms

      return () => clearTimeout(timeoutId);
    }
  }, [fromAmount, selectedFrom, selectedTo, operationType]);
  const handleConvert = async () => {
    if (!fromAmount || !selectedFrom || !selectedTo) {
      return;
    }

    const parsedAmount = parseFloat(fromAmount);

    if (isNaN(parsedAmount) || parsedAmount < 0) {
      setError("Kwota nie może być ujemna.");
      setToAmount("");
      setFullPrecisionAmount("");
      return;
    }

    setError("");

    try {
      const response = await fetch("/api/calculate", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          amount: parseFloat(fromAmount),
          fromCurrency: selectedFrom,
          toCurrency: selectedTo,
          operationType: operationType,
        }),
      });

      if (!response.ok) {
        throw new Error(`API error: ${response.status}`);
      }
      const data = await response.json(); // Rounding function according to exchange office practices
      const roundForOperation = (value, operation) => {
        const factor = 100; // two decimal places
        if (operation === "buy") {
          // client buys currency – exchange office sells → floor rounding
          return Math.floor(value * factor) / factor;
        }
        if (operation === "sell") {
          // client sells currency – exchange office buys → ceil rounding
          return Math.ceil(value * factor) / factor;
        }
        // mid rate → standard mathematical rounding (half-up)
        return Math.round(value * factor) / factor;
      };

      setFullPrecisionAmount(data.result.toFixed(4));
      const roundedResult = roundForOperation(data.result, operationType);
      setToAmount(roundedResult.toFixed(2));
    } catch (error) {
      console.error("Error calculating conversion:", error);
      setToAmount("Niedostępne");
      setFullPrecisionAmount("0.00");
    }
  };

  const swapCurrencies = () => {
    setSelectedFrom(selectedTo);
    setSelectedTo(selectedFrom);
    setFromAmount(toAmount);
    setToAmount(fromAmount);

    setOperationType((prev) => {
      if (prev === "buy") return "sell";
      if (prev === "sell") return "buy";
      return "mid";
    });
  };

  if (!isOpen) return null;

  return (
    <div className="popup-overlay">
      <div className="calculator-popup">
        <div className="popup-header">
          <h3>Kalkulator Walut</h3>
          <button className="close-btn" onClick={onClose}>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path
                d="M18 6L6 18M6 6L18 18"
                stroke="currentColor"
                strokeWidth="2"
              />
            </svg>
          </button>
        </div>{" "}
        <div className="calculator-content">
          <div className="operation-type-selector">
            <div className="operation-buttons">
              <button
                className={`operation-btn ${
                  operationType === "mid" ? "active" : ""
                }`}
                onClick={() => setOperationType("mid")}
              >
                {" "}
                Kurs bez marży
              </button>
              <button
                className={`operation-btn ${
                  operationType === "buy" ? "active" : ""
                }`}
                onClick={() => setOperationType("buy")}
              >
                Kupno
              </button>
              <button
                className={`operation-btn ${
                  operationType === "sell" ? "active" : ""
                }`}
                onClick={() => setOperationType("sell")}
              >
                Sprzedaż
              </button>
            </div>
          </div>

          <div className="conversion-display">
            <div className="currency-selector from">
              <div className="currency-display">
                <CurrencyFlag codeIso={selectedFrom} size="2rem" />
                <select
                  value={selectedFrom}
                  onChange={(e) => setSelectedFrom(e.target.value)}
                  className="currency-select"
                >
                  <option value="PLN">PLN - Polski złoty</option>
                  {currencies.map((currency) => (
                    <option key={currency.codeIso} value={currency.codeIso}>
                      {currency.codeIso} - {currency.name}
                    </option>
                  ))}
                </select>
              </div>{" "}
              <div className="current-rate">
                Kurs:{" "}
                {selectedFrom === "PLN"
                  ? "1.0000"
                  : (() => {
                      const currency = currencies.find(
                        (c) => c.codeIso === selectedFrom
                      );
                      if (!currency) return "N/A";

                      let rate = currency.currentMid;
                      if (operationType === "buy" && currency.buyRate) {
                        rate = currency.buyRate;
                      } else if (
                        operationType === "sell" &&
                        currency.sellRate
                      ) {
                        rate = currency.sellRate;
                      }

                      return parseFloat(rate).toFixed(4);
                    })()}{" "}
                PLN
              </div>
            </div>

            <button className="swap-button" onClick={swapCurrencies}>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path
                  d="M16 3l4 4-4 4M20 7H4M8 21l-4-4 4-4M4 17h16"
                  stroke="currentColor"
                  strokeWidth="2"
                />
              </svg>
            </button>

            <div className="currency-selector to">
              <div className="currency-display">
                <CurrencyFlag codeIso={selectedTo} size="2rem" />
                <select
                  value={selectedTo}
                  onChange={(e) => setSelectedTo(e.target.value)}
                  className="currency-select"
                >
                  <option value="PLN">PLN - Polski złoty</option>
                  {currencies.map((currency) => (
                    <option key={currency.codeIso} value={currency.codeIso}>
                      {currency.codeIso} - {currency.name}
                    </option>
                  ))}
                </select>
              </div>{" "}
              <div className="current-rate">
                Kurs:{" "}
                {selectedTo === "PLN"
                  ? "1.0000"
                  : (() => {
                      const currency = currencies.find(
                        (c) => c.codeIso === selectedTo
                      );
                      if (!currency) return "N/A";

                      let rate = currency.currentMid;
                      if (operationType === "buy" && currency.buyRate) {
                        rate = currency.buyRate;
                      } else if (
                        operationType === "sell" &&
                        currency.sellRate
                      ) {
                        rate = currency.sellRate;
                      }

                      return parseFloat(rate).toFixed(4);
                    })()}{" "}
                PLN
              </div>
            </div>
          </div>

          <div className="conversion-inputs">
            <div className="input-group">
              <label>Mam ({selectedFrom}):</label>
              <input
                type="number"
                min="0.1"
                value={fromAmount}
                onChange={(e) => {
                  setFromAmount(e.target.value);
                  setError("");
                }}
                className="amount-input"
                placeholder="0.00"
                step="0.01"
              />
              {error && <p className="text-red-500 text-sm mt-1">{error}</p>}
            </div>

            <div className="input-group">
              <label>Chcę otrzymać ({selectedTo}):</label>
              <input
                type="text"
                value={
                  toAmount && fullPrecisionAmount
                    ? `${toAmount} (${fullPrecisionAmount})`
                    : ""
                }
                readOnly
                className="amount-input result"
                placeholder="0.00"
              />
            </div>
          </div>

          <button className="convert-btn" onClick={handleConvert}>
            <svg
              className="btn-icon"
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
            >
              <path
                d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"
                stroke="currentColor"
                strokeWidth="2"
              />
              <rect
                x="8"
                y="2"
                width="8"
                height="4"
                rx="1"
                ry="1"
                stroke="currentColor"
                strokeWidth="2"
              />
            </svg>
            Przelicz
          </button>
        </div>
      </div>
    </div>
  );
};

export default CalculatorPopup;
