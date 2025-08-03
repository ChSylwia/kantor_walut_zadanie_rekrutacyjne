import React, { useState, useEffect, useMemo, useCallback } from "react";
import Navbar from "./Navbar";
import CurrencyCard from "./CurrencyCard";
import CalculatorPopup from "./CalculatorPopup";
import HistoryPopup from "./HistoryPopup";

const CurrencyExchangeApp = () => {
  const [currencies, setCurrencies] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [isCalculatorOpen, setIsCalculatorOpen] = useState(false);
  const [isHistoryOpen, setIsHistoryOpen] = useState(false);
  const [calculatorCurrencies, setCalculatorCurrencies] = useState({
    from: "USD",
    to: "PLN",
  });
  const [selectedHistoryCurrency, setSelectedHistoryCurrency] = useState("");
  useEffect(() => {
    fetchCurrencies();
  }, []);

  const filteredCurrencies = useMemo(() => {
    if (searchTerm.trim() === "") {
      return currencies;
    }
    const lowerSearchTerm = searchTerm.toLowerCase();
    return currencies.filter(
      (currency) =>
        currency.codeIso.toLowerCase().includes(lowerSearchTerm) ||
        currency.name.toLowerCase().includes(lowerSearchTerm)
    );
  }, [searchTerm, currencies]);

  const fetchCurrencies = async () => {
    try {
      const response = await fetch("/api/exchange-rates");

      if (!response.ok) {
        throw new Error(`API error: ${response.status}`);
      }
      const data = await response.json();

      setCurrencies(data);
    } catch (error) {
      console.error("Error fetching currencies:", error);
    }
  };
  const handleCurrencySelect = useCallback((fromCurrency, toCurrency) => {
    setCalculatorCurrencies({ from: fromCurrency, to: toCurrency });
    setIsCalculatorOpen(true);
  }, []);

  const handleCardClick = useCallback((currencyCode) => {
    setSelectedHistoryCurrency(currencyCode);
    setIsHistoryOpen(true);
  }, []);

  const handleCalculatorClose = useCallback(
    () => setIsCalculatorOpen(false),
    []
  );
  const handleHistoryClose = useCallback(() => setIsHistoryOpen(false), []);
  const handleCalculatorClick = useCallback(
    () => setIsCalculatorOpen(true),
    []
  );
  const handleHistoryClick = useCallback(() => setIsHistoryOpen(true), []);

  const availableCurrencies = useMemo(
    () => currencies.map((currency) => currency.codeIso),
    [currencies]
  );

  return (
    <div className="app">
      {" "}
      <Navbar
        searchTerm={searchTerm}
        onSearchChange={setSearchTerm}
        onCalculatorClick={handleCalculatorClick}
        onHistoryClick={handleHistoryClick}
      />
      <div className="main-content">
        <div className="currencies-grid">
          {filteredCurrencies.map((currency) => (
            <CurrencyCard
              key={currency.codeIso}
              currency={currency}
              onCurrencySelect={handleCurrencySelect}
              onCardClick={handleCardClick}
              availableCurrencies={availableCurrencies}
            />
          ))}
        </div>
      </div>
      <CalculatorPopup
        isOpen={isCalculatorOpen}
        onClose={handleCalculatorClose}
        fromCurrency={calculatorCurrencies.from}
        toCurrency={calculatorCurrencies.to}
        currencies={currencies}
      />
      <HistoryPopup
        isOpen={isHistoryOpen}
        onClose={handleHistoryClose}
        selectedCurrencyCode={selectedHistoryCurrency}
        currencies={currencies}
      />
    </div>
  );
};

export default CurrencyExchangeApp;
