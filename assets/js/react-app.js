(function () {
    const { useEffect, useState } = React;

    /**
     * @typedef {Object} Product
     * @property {number} id
     * @property {string} name
     * @property {string} image
     * @property {string} link
     * @property {string} price_html
     */

    /**
     * Loading spinner component
     */
    function LoadingSpinner() {
        return (
            <div className="awr-loading-spinner">
                <div className="spinner"></div>
                <p>Loading recommendations...</p>
            </div>
        );
    }

    /**
     * Error message component
     * @param {{ message: string }} props
     */
    function ErrorMessage({ message }) {
        return (
            <div className="awr-error-message">
                <p>{message}</p>
            </div>
        );
    }

    /**
     * Product card component
     * @param {{ product: Product }} props
     */
    function ProductCard({ product }) {
        return (
            <div className="awr-product" key={product.id}>
                <a href={product.link}>
                    <div className="awr-product-image">
                        <img 
                            src={product.image} 
                            alt={product.name}
                            onError={(e) => {
                                e.target.onerror = null;
                                e.target.src = 'placeholder-image.jpg';
                            }}
                        />
                    </div>
                    <div className="awr-product-details">
                        <h2>{product.name}</h2>
                        <p 
                            className="awr-price"
                            dangerouslySetInnerHTML={{ __html: product.price_html }}
                        />
                    </div>
                </a>
            </div>
        );
    }

    /**
     * Main recommendations component
     */
    function Recommendations() {
        const [products, setProducts] = useState([]);
        const [loading, setLoading] = useState(true);
        const [error, setError] = useState(null);
        
        useEffect(() => {
            const fetchRecommendations = async () => {
                try {
                    const response = await fetch(
                        `${awr_data.apiEndpoint}?user_id=${awr_data.userId}`,
                        {
                            headers: {
                                'X-WP-Nonce': awr_data.nonce,
                                'Content-Type': 'application/json'
                            }
                        }
                    );

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (!Array.isArray(data)) {
                        throw new Error('Invalid response format');
                    }

                    setProducts(data);
                    setError(null);
                } catch (error) {
                    console.error('Error fetching recommendations:', error);
                    setError('Failed to load recommendations. Please try again later.');
                } finally {
                    setLoading(false);
                }
            };

            fetchRecommendations();

            // Cleanup function
            return () => {
                setProducts([]);
                setLoading(true);
                setError(null);
            };
        }, []);

        if (loading) {
            return <LoadingSpinner />;
        }

        if (error) {
            return <ErrorMessage message={error} />;
        }

        return (
            <div className="awr-recommendations">
                <div className="awr-recommendations-grid">
                    {products.length > 0 ? (
                        products.map((product) => (
                            <ProductCard 
                                key={product.id} 
                                product={product} 
                            />
                        ))
                    ) : (
                        <div className="awr-no-recommendations">
                            <p>No recommendations available at this time.</p>
                        </div>
                    )}
                </div>
            </div>
        );
    }

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('awr-recommendations-root');
        if (container) {
            ReactDOM.render(
                <React.StrictMode>
                    <Recommendations />
                </React.StrictMode>,
                container
            );
        }
    });
})();
