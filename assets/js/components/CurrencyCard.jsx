import React from "react";
import CurrencyFlag from "./CurrencyFlag";
import { getTrendIcon } from "../utils/trendUtils";

const CurrencyCard = ({
  currency,
  onCurrencySelect,
  onCardClick,
  availableCurrencies,
}) => {
  const trend = getTrendIcon(currency.currentMid, currency.previousMid);

  return (
    <div
      className="currency-card"
      onClick={() => onCardClick(currency.codeIso)}
    >
      <div className="card-header">
        <div className="currency-info">
          <CurrencyFlag codeIso={currency.codeIso} size="2rem" />
          <div className="currency-details">
            <span className="currency-code">{currency.codeIso}</span>
            <span className="currency-name-short">{currency.name}</span>
          </div>
        </div>
        <div className={`trend ${trend.class}`}>
          <span className="trend-icon">{trend.icon}</span>
          <span className="trend-label">{trend.label}</span>
        </div>
      </div>

      <div className="card-body">
        <div className="rate-current">
          <span className="rate-value">
            {parseFloat(currency.currentMid).toFixed(4)}
          </span>
          <span className="rate-currency">PLN</span>
        </div>

        <div className="currency-details-grid">
          <div className="detail-row">
            <span className="detail-label">Opublikowano:</span>
            <span className="detail-value">{currency.effectiveDate}</span>
          </div>

          <div className="detail-row">
            <span className="detail-label">Aktualizacja:</span>
            <span className="detail-value">{currency.updatedAt}</span>
          </div>

          <div className="rates-grid">
            <div className="rate-box buy">
              <span className="rate-label">Kupno</span>
              <span className="rate-value">
                {currency.buyRate
                  ? `${parseFloat(currency.buyRate).toFixed(4)} PLN`
                  : "Niedostępne"}
              </span>
            </div>

            <div className="rate-box sell">
              <span className="rate-label">Sprzedaż</span>
              <span className="rate-value">
                {currency.sellRate
                  ? `${parseFloat(currency.sellRate).toFixed(4)} PLN`
                  : "Niedostępne"}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div className="card-footer">
        <div className="available-currencies">
          <span className="footer-label">Przelicz na:</span>
          <div className="currency-chips">
            {availableCurrencies
              .filter((code) => code !== currency.codeIso)
              .map((code) => (
                <button
                  key={code}
                  className="currency-chip"
                  onClick={(e) => {
                    e.stopPropagation();
                    onCurrencySelect(currency.codeIso, code);
                  }}
                >
                  <CurrencyFlag codeIso={code} size="1rem" />
                  {code}
                </button>
              ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default CurrencyCard;
