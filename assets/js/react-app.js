(function () {
    const { useEffect, useState } = React;

    function Recommendations() {
        const [products, setProducts] = useState([]);
        const [loading, setLoading] = useState(true);
        
        useEffect(() => {
            fetch(`${awr_data.apiEndpoint}?user_id=${awr_data.userId}`)
                .then((response) => response.json())
                .then((data) => {
                    setProducts(data);
                    setLoading(false);
                })
                .catch((error) => {
                    console.error('Error fetching recommendations:', error);
                    setLoading(false);
                });
        }, []);

        if (loading) {
            return <div>Loading recommendations...</div>;
        }

        return (
            <div className="awr-recommendations-grid">
                {products.length > 0 ? (
                    products.map((product) => (
                        <div className="awr-product" key={product.id}>
                            <a href={product.link}>
                                <img src={product.image} alt={product.name} />
                                <h2>{product.name}</h2>
                                <p className="awr-price">{product.price_html}</p>
                            </a>
                        </div>
                    ))
                ) : (
                    <p>No recommendations available at this time.</p>
                )}
            </div>
        );
    }

    // Render the React component
    ReactDOM.render(
        <Recommendations />,
        document.getElementById('awr-recommendations-root')
    );
})();
