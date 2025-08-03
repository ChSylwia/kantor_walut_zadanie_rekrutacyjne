import { useState, useEffect } from "react";

let cachedMapping = null;
let isLoading = false;
let subscribers = [];

const useFlagsMapping = () => {
  const [mapping, setMapping] = useState(cachedMapping);
  const [loading, setLoading] = useState(isLoading);

  useEffect(() => {
    if (cachedMapping) {
      setMapping(cachedMapping);
      setLoading(false);
      return;
    }

    if (isLoading) {
      subscribers.push(setMapping);
      return;
    }

    const fetchMapping = async () => {
      isLoading = true;
      setLoading(true);

      try {
        const response = await fetch("/api/currencies/flags");
        if (response.ok) {
          const data = await response.json();
          cachedMapping = data;
          setMapping(data);

          // Notify all subscribers
          subscribers.forEach((callback) => callback(data));
          subscribers = [];
        }
      } catch (error) {
        console.error("Error fetching flags mapping:", error);
      } finally {
        isLoading = false;
        setLoading(false);
      }
    };

    fetchMapping();
  }, []);

  return { mapping, loading };
};

export default useFlagsMapping;
