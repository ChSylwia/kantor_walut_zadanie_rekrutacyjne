import React, { useState, useEffect } from "react";
import CurrencyFlag from "./CurrencyFlag";
import { getTrendIcon } from "../utils/trendUtils";

const HistoryPopup = ({
  isOpen,
  onClose,
  selectedCurrencyCode = "",
  currencies = [],
}) => {
  const [selectedCurrency, setSelectedCurrency] = useState(
    selectedCurrencyCode || "USD"
  );
  const [selectedDate, setSelectedDate] = useState("");
  const [historyData, setHistoryData] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  useEffect(() => {
    if (isOpen && selectedCurrencyCode) {
      setSelectedCurrency(selectedCurrencyCode);
    }
  }, [isOpen, selectedCurrencyCode]);

  useEffect(() => {
    if (isOpen && selectedCurrency) {
      setError(null);
    }
  }, [selectedCurrency, isOpen]);
  const fetchHistory = async () => {
    setIsLoading(true);
    setError(null);
    try {
      let url = `/api/currency-history/${selectedCurrency}`;
      if (selectedDate) {
        url += `?endDate=${selectedDate}`;
      }

      const response = await fetch(url);
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || `API error: ${response.status}`);
      }
      const data = await response.json();

      if (!data.rates || !Array.isArray(data.rates)) {
        throw new Error("Invalid data format received");
      }

      const rates = data.rates;

      // Sort rates by date (newest first) to ensure proper order
      const sortedRates = rates.sort(
        (a, b) => new Date(b.effectiveDate) - new Date(a.effectiveDate)
      );

      const transformedHistory = sortedRates.map((rate, index) => ({
        effectiveDate: rate.effectiveDate,
        currentMid: rate.mid,
        previousMid:
          index + 1 < sortedRates.length
            ? sortedRates[index + 1].mid
            : rate.mid,
      }));

      setHistoryData(transformedHistory);
    } catch (error) {
      console.error("Error fetching history:", error);
      setError(error.message);
      setHistoryData([]);
    } finally {
      setIsLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="popup-overlay">
      <div className="history-popup">
        <div className="popup-header">
          <h3>Historia Walut</h3>
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
        <div className="history-controls">
          <div className="control-group">
            <label>Waluta:</label>
            <div className="currency-select-container">
              <CurrencyFlag codeIso={selectedCurrency} size="1.5rem" />
              <select
                value={selectedCurrency}
                onChange={(e) => setSelectedCurrency(e.target.value)}
                className="currency-select"
              >
                {currencies.map((currency) => (
                  <option key={currency.codeIso} value={currency.codeIso}>
                    {currency.codeIso} - {currency.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="control-group">
            <label>Historia sprzed (opcjonalnie):</label>
            <input
              type="date"
              value={selectedDate}
              onChange={(e) => setSelectedDate(e.target.value)}
              className="date-input"
              max={new Date().toISOString().split("T")[0]}
            />
            <small style={{ color: "#6b7280", fontSize: "0.75rem" }}>
              Pozostaw puste dla ostatnich 14 notowań
            </small>
          </div>

          <div className="control-group full-width">
            <button
              className="fetch-btn full-width"
              onClick={fetchHistory}
              disabled={isLoading}
            >
              {isLoading ? (
                <>
                  <div className="spinner"></div>
                  Ładowanie...
                </>
              ) : (
                <>
                  <svg
                    className="btn-icon"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                  >
                    <path
                      d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9"
                      stroke="currentColor"
                      strokeWidth="2"
                    />
                    <path
                      d="M9 12l2 2 4-4"
                      stroke="currentColor"
                      strokeWidth="2"
                    />
                  </svg>
                  Pobierz historię
                </>
              )}
            </button>
          </div>
        </div>{" "}
        <div className="history-table-container">
          {error && (
            <div className="error-message">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <circle
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="#ef4444"
                  strokeWidth="2"
                />
                <path d="m15 9-6 6" stroke="#ef4444" strokeWidth="2" />
                <path d="m9 9 6 6" stroke="#ef4444" strokeWidth="2" />
              </svg>
              <p>{error}</p>
            </div>
          )}
          {historyData.length > 0 ? (
            <table className="history-table">
              <thead>
                <tr>
                  <th>Data</th>
                  <th>Kurs (PLN)</th>
                  <th>Zmiana</th>
                  <th>Trend</th>
                </tr>
              </thead>{" "}
              <tbody>
                {historyData.map((item, index) => {
                  const trend = getTrendIcon(
                    item.currentMid,
                    item.previousMid,
                    false
                  );
                  const currentVal = parseFloat(item.currentMid);
                  const previousVal = parseFloat(item.previousMid);

                  let changePercent = "0.00";
                  if (
                    !isNaN(currentVal) &&
                    !isNaN(previousVal) &&
                    previousVal !== 0
                  ) {
                    changePercent = (
                      ((currentVal - previousVal) * 100) /
                      previousVal
                    ).toFixed(2);
                  }

                  return (
                    <tr key={index}>
                      <td>{item.effectiveDate}</td>
                      <td className="rate-cell">
                        {isNaN(currentVal) ? "N/A" : currentVal.toFixed(4)}
                      </td>
                      <td className={`change-cell ${trend.class}`}>
                        {changePercent}%
                      </td>
                      <td className={`trend-cell ${trend.class}`}>
                        <span className="trend-icon">{trend.icon}</span>
                      </td>
                    </tr>
                  );
                })}{" "}
              </tbody>
            </table>
          ) : (
            !error && (
              <div className="no-data">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
                  <circle
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="#cbd5e1"
                    strokeWidth="2"
                  />
                  <path d="m9 12 2 2 4-4" stroke="#cbd5e1" strokeWidth="2" />
                </svg>
                <p>Brak danych historycznych</p>
                <p>Wybierz walutę i kliknij "Pobierz historię"</p>
              </div>
            )
          )}
        </div>
      </div>
    </div>
  );
};

export default HistoryPopup;
