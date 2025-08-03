import React from "react";

const Navbar = ({
  searchTerm,
  onSearchChange,
  onCalculatorClick,
  onHistoryClick,
}) => {
  return (
    <nav className="navbar-container">
      <div className="navbar-content">
        <div className="search-input-container">
          <svg
            className="search-icon"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
          >
            <circle cx="11" cy="11" r="8" stroke="#2563eb" strokeWidth="2" />
            <path d="21 21l-4.35-4.35" stroke="#2563eb" strokeWidth="2" />
          </svg>
          <input
            type="text"
            placeholder="Wyszukaj walutę (np. USD, EUR)..."
            value={searchTerm}
            onChange={(e) => onSearchChange(e.target.value)}
            className="search-input"
          />
        </div>

        <div className="navbar-buttons">
          <button
            className="nav-button calculator-btn"
            onClick={onCalculatorClick}
          >
            <svg
              className="btn-icon"
              width="18"
              height="18"
              viewBox="0 0 24 24"
              fill="none"
            >
              <rect
                x="4"
                y="2"
                width="16"
                height="20"
                rx="2"
                stroke="currentColor"
                strokeWidth="2"
              />
              <path
                d="8 6h8M8 10h8M8 14h4M8 18h4"
                stroke="currentColor"
                strokeWidth="2"
              />
            </svg>
            Kalkulator walut
          </button>

          <button className="nav-button history-btn" onClick={onHistoryClick}>
            <svg
              className="btn-icon"
              width="18"
              height="18"
              viewBox="0 0 24 24"
              fill="none"
            >
              <path
                d="M3 3v5h5M3.05 13A9 9 0 1 0 6 5.3L3 8"
                stroke="currentColor"
                strokeWidth="2"
              />
              <path d="12 7v5l4 2" stroke="currentColor" strokeWidth="2" />
            </svg>
            Historia walut
          </button>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
