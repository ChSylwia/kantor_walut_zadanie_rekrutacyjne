export const getTrendIcon = (
  currentRate,
  previousRate,
  includePercentage = true
) => {
  const currentVal = parseFloat(currentRate);
  const previousVal = parseFloat(previousRate);

  if (isNaN(currentVal) || isNaN(previousVal) || previousVal === 0) {
    return {
      icon: "→",
      class: "trend-neutral",
      label: includePercentage ? "Bez zmian" : "→",
    };
  }

  const diff = currentVal - previousVal;
  const percent = ((diff / previousVal) * 100).toFixed(2);

  if (diff > 0) {
    return {
      icon: "▲",
      class: "trend-up",
      label: includePercentage ? `+${diff.toFixed(4)} (+${percent}%)` : "▲",
    };
  }

  if (diff < 0) {
    return {
      icon: "▼",
      class: "trend-down",
      label: includePercentage ? `${diff.toFixed(4)} (${percent}%)` : "▼",
    };
  }

  return {
    icon: "→",
    class: "trend-neutral",
    label: includePercentage ? "Bez zmian" : "→",
  };
};
