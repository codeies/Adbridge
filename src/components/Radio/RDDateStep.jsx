import useCampaignStore from "@/stores/useCampaignStore";


const RDDateStep = () => {
    const { radio } = useCampaignStore();

    if (!radio || !radio.pricing) {
        console.error("Radio or pricing data is missing", radio);
        return <div>Loading...</div>; // Prevent crash by returning fallback UI
    }

    return (
        <div>
            <h2>Radio Date Selection</h2>

            <h3>Jingles Pricing</h3>
            {radio.pricing.jingles.length > 0 ? (
                <ul>
                    {radio.pricing.jingles.map((jingle, index) => (
                        <li key={index}>
                            {jingle.name} - Price Per Slot: ${jingle.pricePerSlot}, Max Slots: {jingle.maxSlots}
                        </li>
                    ))}
                </ul>
            ) : (
                <p>No jingles available</p>
            )}

            <h3>Announcements Pricing</h3>
            {radio.pricing.announcements.length > 0 ? (
                <ul>
                    {radio.pricing.announcements.map((announcement, index) => (
                        <li key={index}>
                            {announcement.name} - Price Per Slot: ${announcement.pricePerSlot}, Max Slots: {announcement.maxSlots}
                        </li>
                    ))}
                </ul>
            ) : (
                <p>No announcements available</p>
            )}
        </div>
    );
};

export default RDDateStep;
