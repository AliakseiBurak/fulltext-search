import React, { useState } from 'react';
import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8080';

function App() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleSearch = async (e) => {
    e.preventDefault();
    if (!query.trim()) return;

    setLoading(true);
    setError(null);
    try {
      const response = await axios.get(`${API_URL}/api/search`, {
        params: { q: query }
      });
      setResults(response.data.results || []);
    } catch (err) {
      setError('Search failed. Please try again.');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="App" style={{ padding: '20px', maxWidth: '800px', margin: '0 auto' }}>
      <h1>Manticore Search</h1>
      
      <form onSubmit={handleSearch} style={{ marginBottom: '20px' }}>
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Enter search query..."
          style={{
            width: '70%',
            padding: '10px',
            fontSize: '16px',
            marginRight: '10px'
          }}
        />
        <button
          type="submit"
          disabled={loading}
          style={{
            padding: '10px 20px',
            fontSize: '16px',
            cursor: loading ? 'not-allowed' : 'pointer'
          }}
        >
          {loading ? 'Searching...' : 'Search'}
        </button>
      </form>

      {error && <div style={{ color: 'red', marginBottom: '20px' }}>{error}</div>}

      {results.length > 0 && (
        <div>
          <h2>Results ({results.length})</h2>
          {results.map((item, index) => (
            <div
              key={index}
              style={{
                border: '1px solid #ddd',
                padding: '15px',
                marginBottom: '10px',
                borderRadius: '4px'
              }}
            >
              <h3>{item.title || 'Untitled'}</h3>
              <p>{item.content || item.body || ''}</p>
              {item.score && <small>Score: {item.score}</small>}
            </div>
          ))}
        </div>
      )}

      {!loading && results.length === 0 && query && (
        <p>No results found.</p>
      )}
    </div>
  );
}

export default App;
