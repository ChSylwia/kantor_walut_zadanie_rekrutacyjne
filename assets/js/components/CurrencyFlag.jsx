import React, { useState } from "react";
import useFlagsMapping from "../hooks/useFlagsMapping";

const CurrencyFlag = ({ codeIso, size = "1.5rem" }) => {
  const [imageError, setImageError] = useState(false);
  const { mapping } = useFlagsMapping();

  const countryCode = mapping ? mapping[codeIso] || "un" : "un";

  if (!countryCode || countryCode === "un" || imageError) {
    return <span style={{ fontSize: size }}>🏳️</span>;
  }

  const flagUrl = `https://flagcdn.com/w40/${countryCode}.png`;

  return (
    <img
      src={flagUrl}
      alt={`${codeIso} flag`}
      style={{
        width: size,
        height: size,
        objectFit: "contain",
        borderRadius: "2px",
      }}
      onError={() => setImageError(true)}
    />
  );
};

export default CurrencyFlag;
