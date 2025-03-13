import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import useCampaignStore from "@/stores/useCampaignStore";
import { useEffect, useState } from "react";
import axios from 'axios';
import StepHeader from "@/components/StepHeader";

const RadioStep = () => {
    const {
        radio,
        setRadioFilters,
        setCurrentStep,
        campaignType
    } = useCampaignStore();

    const [radioStations, setRadioStations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchStations = async () => {
            setLoading(true);
            setError(null);
            try {
                const type = campaignType === "tv" ? "tv" : "radio"; // Adjust based on campaignType
                const response = await axios.get(`${adbridgeData.restUrl}adrentals/v1/campaigns?adrentals_type=${type}`);
                setRadioStations(response.data);
            } catch (err) {
                setError(err);
                console.error("Error fetching stations:", err);
            } finally {
                setLoading(false);
            }
        };

        fetchStations();
    }, [campaignType]);

    // Extract unique station types and locations from data
    const stationTypes = ["all", ...new Set(radioStations.flatMap(station => station.adrental_category))];
    const locations = ["all", ...new Set(radioStations.flatMap(station => station.adrental_location))];

    // Corrected filtering logic
    const filteredStations = radioStations.filter((r) => {
        const matchesType = radio.selectedCategory === "all" || r.adrental_category?.includes(radio.selectedCategory);
        const matchesLocation = radio.selectedLocation === "all" || r.adrental_location?.includes(radio.selectedLocation);
        const matchesSearch = radio.searchTerm ? r.title?.toLowerCase().includes(radio.searchTerm.toLowerCase()) : true;

        return matchesType && matchesLocation && matchesSearch;
    });

    if (loading) {
        return <div>Loading {campaignType === "tv" ? "TV Channels" : "Radio Stations"}...</div>;
    }

    if (error) {
        return <div>Error loading {campaignType === "tv" ? "TV Channels" : "Radio Stations"}.</div>;
    }

    return (
        <div className="space-y-6 relative">

            <StepHeader
                title={campaignType === "tv" ? "Select TV Channel" : "Select Radio Station"}
                onBack={() => setCurrentStep(1)}
            />

            {/*             <div className="flex items-center space-x-4">
                <Button
                    variant="outline"
                    onClick={() => setCurrentStep(1)}
                    className="flex items-center"
                >
                    ← Back
                </Button>
                <h2 className="text-2xl font-semibold">
                    {campaignType === "tv" ? "Select TV Channel" : "Select Radio Station"}
                </h2>
            </div> */}

            <p className="text-gray-600">
                {campaignType === "tv"
                    ? "Choose a TV channel for your campaign. You can filter by type, location, or search by name."
                    : "Choose a radio station for your campaign. You can filter by type, location, or search by name."}
            </p>

            {/* Search & Filters */}
            <div className="grid gap-4 mb-6 sm:grid-cols-2 md:flex md:flex-wrap">
                {/* Search Input */}
                <div className="flex-1 min-w-[150px] sm:min-w-[200px]">
                    <Input
                        type="text"
                        placeholder={`Search ${campaignType === "tv" ? "TV Channels" : "Radio Stations"}...`}
                        value={radio.searchTerm}
                        onChange={(e) => setRadioFilters({ searchTerm: e.target.value })}
                        className="w-full"
                    />
                </div>

                {/* Dynamic Station Type Filter */}
                <div className="relative z-20 min-w-[130px] sm:min-w-[150px]">
                    <Select
                        value={radio.selectedCategory}
                        onValueChange={(value) => setRadioFilters({ selectedCategory: value })}
                    >
                        <SelectTrigger className="w-full sm:w-48">
                            <SelectValue placeholder="Type" />
                        </SelectTrigger>
                        <SelectContent position="popper" className="w-full sm:w-48">
                            {stationTypes.map(type => (
                                <SelectItem key={type} value={type}>
                                    {type.charAt(0).toUpperCase() + type.slice(1)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Dynamic Location Filter */}
                <div className="relative z-20 min-w-[130px] sm:min-w-[150px]">
                    <Select
                        value={radio.selectedLocation}
                        onValueChange={(value) => setRadioFilters({ selectedLocation: value })}
                    >
                        <SelectTrigger className="w-full sm:w-48">
                            <SelectValue placeholder="Location" />
                        </SelectTrigger>
                        <SelectContent position="popper" className="w-full sm:w-48">
                            {locations.map(location => (
                                <SelectItem key={location} value={location}>
                                    {location.charAt(0).toUpperCase() + location.slice(1)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>


            {/* Stations List */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {filteredStations.map((station) => (
                    <Card
                        key={station.id}
                        className="cursor-pointer hover:bg-gray-50"
                        onClick={() => {
                            setRadioFilters({ selectedStation: station });
                            setCurrentStep(3);
                        }}
                    >
                        <CardContent className="p-4">
                            {station.featured_image && station.featured_image !== false ? (
                                <img
                                    src={station.featured_image}
                                    alt={station.title}
                                    className="w-full h-auto mb-4 rounded"
                                />
                            ) : (
                                <div className="aspect-video bg-gray-200 mb-4 rounded"></div>
                            )}
                            <h3 className="font-semibold">{station.title}</h3>
                            {station.attributes?.map((attr, index) => (
                                <p key={index} className="text-sm text-gray-600">
                                    {attr.attribute}: {attr.value}
                                </p>
                            ))}
                        </CardContent>

                    </Card>
                ))}
            </div>
        </div>
    );
};

export default RadioStep;